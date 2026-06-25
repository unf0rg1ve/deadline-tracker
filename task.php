<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Submission.php';
require_once __DIR__ . '/models/Task.php';
require_once __DIR__ . '/models/TaskAttachment.php';
require_once __DIR__ . '/models/TaskComment.php';
require_once __DIR__ . '/models/Notification.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$taskId = (int) ($_GET['id'] ?? 0);
$task = Task::findForUser($taskId, (int) $_SESSION['user_id'], $_SESSION['role']);

if (!$task) {
    header('Location: index.php');
    exit;
}

$error = '';
$commentError = '';

if ($_SESSION['role'] === 'student' && isset($_POST['submit_work'])) {
    $content = trim($_POST['content'] ?? '');
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($content) < 10) {
        $error = 'Текст сдачи должен быть не короче 10 символов.';
    } else {
        Submission::save($taskId, (int) $_SESSION['user_id'], $content);
        Notification::notifyCourseTeachersAndAdmins(
            (int) $task['course_id'],
            'work_submitted',
            'Новая сдача работы',
            $task['title'],
            'task.php?id=' . $taskId,
            (int) $_SESSION['user_id']
        );
        flash('success', t('work_submitted'));
        header('Location: task.php?id=' . $taskId);
        exit;
    }
}

if (isset($_POST['submit_comment'])) {
    $body = trim($_POST['body'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $commentError = t('csrf_error');
    } elseif (strlen($body) < 2) {
        $commentError = 'Комментарий должен быть не короче 2 символов.';
    } else {
        TaskComment::create($taskId, (int) $_SESSION['user_id'], $body);
        if ($_SESSION['role'] === 'student') {
            Notification::notifyCourseTeachersAndAdmins(
                (int) $task['course_id'],
                'task_comment',
                'Новый комментарий к заданию',
                $task['title'],
                'task.php?id=' . $taskId,
                (int) $_SESSION['user_id']
            );
        } else {
            Notification::notifyCourseStudents(
                (int) $task['course_id'],
                'task_comment',
                'Новый комментарий к заданию',
                $task['title'],
                'task.php?id=' . $taskId,
                (int) $_SESSION['user_id']
            );
        }
        flash('success', 'Комментарий добавлен.');
        header('Location: task.php?id=' . $taskId);
        exit;
    }
}

$studentSubmission = $_SESSION['role'] === 'student'
    ? Submission::findForTaskAndUser($taskId, (int) $_SESSION['user_id'])
    : null;
$submissions = $_SESSION['role'] === 'student' ? [] : Submission::forTask($taskId);
$attachments = TaskAttachment::forTask($taskId);
$comments = TaskComment::forTask($taskId);

include 'header.php';
?>

<main>
    <h1><?php echo e($task['title']); ?></h1>
    <p><?php echo e($task['description']); ?></p>
    <p><strong>Курс:</strong> <?php echo e($task['course_title']); ?></p>
    <p><strong>Дедлайн:</strong> <?php echo e(date('d.m.Y', strtotime($task['deadline']))); ?></p>

    <section class="section-block">
        <div class="section-header">
            <h2>Файлы задания</h2>
            <?php if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
                <a href="add_task_attachment.php?task_id=<?php echo (int) $task['id']; ?>">Добавить файл</a>
            <?php endif; ?>
        </div>

        <?php if (empty($attachments)): ?>
            <p>Файлы к заданию пока не добавлены.</p>
        <?php else: ?>
            <div class="resource-list">
                <?php foreach ($attachments as $attachment): ?>
                    <a class="resource-item" href="download.php?type=attachment&id=<?php echo (int) $attachment['id']; ?>">
                        <span class="resource-icon">F</span>
                        <span>
                            <strong><?php echo e($attachment['title']); ?></strong>
                            <small><?php echo e($attachment['original_name']); ?> · <?php echo e(formatFileSize((int) $attachment['file_size'])); ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-header">
            <h2>Обсуждение задания</h2>
        </div>

        <form method="POST" class="composer">
            <?php echo csrfField(); ?>
            <textarea name="body" placeholder="Напишите вопрос, уточнение или ответ" required></textarea>
            <button type="submit" name="submit_comment">Отправить</button>
        </form>
        <?php if ($commentError): ?>
            <p class="error-msg"><?php echo e($commentError); ?></p>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
            <p>Комментариев пока нет.</p>
        <?php else: ?>
            <div class="comment-list">
                <?php foreach ($comments as $comment): ?>
                    <?php preg_match('/./u', $comment['author_name'] ?? 'U', $commentInitial); ?>
                    <article class="comment-item">
                        <div class="comment-avatar"><?php echo e($commentInitial[0] ?? 'U'); ?></div>
                        <div>
                            <div class="comment-meta">
                                <strong><?php echo e($comment['author_name'] ?? 'Автор'); ?></strong>
                                <small><?php echo e($comment['created_at']); ?></small>
                            </div>
                            <p><?php echo nl2br(e($comment['body'])); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
        <p>
            <a href="edit_task.php?id=<?php echo (int) $task['id']; ?>">Редактировать</a>
            |
            <a href="delete_task.php?id=<?php echo (int) $task['id']; ?>">Удалить</a>
        </p>

        <h2>Сдачи студентов</h2>
        <?php if (empty($submissions)): ?>
            <p>Пока никто не сдал работу.</p>
        <?php else: ?>
            <div class="cards-container">
                <?php foreach ($submissions as $submission): ?>
                    <div class="card">
                        <h3><?php echo e($submission['full_name']); ?></h3>
                        <p><?php echo nl2br(e($submission['content'])); ?></p>
                        <small>Статус: <?php echo e(taskStatusLabel($submission['status'])); ?></small>
                        <small>Сдано: <?php echo e($submission['submitted_at']); ?></small>
                        <?php if (!empty($submission['grade'])): ?>
                            <small>Оценка: <?php echo e($submission['grade']); ?></small>
                        <?php endif; ?>
                        <?php if (!empty($submission['feedback'])): ?>
                            <p><strong>Фидбек:</strong> <?php echo nl2br(e($submission['feedback'])); ?></p>
                        <?php endif; ?>
                        <form method="POST" action="review_submission.php" class="login-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="submission_id" value="<?php echo (int) $submission['id']; ?>">
                            <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
                            <select name="status" required>
                                <option value="reviewed">Проверено</option>
                                <option value="needs_revision">Нужна доработка</option>
                            </select>
                            <input type="text" name="grade" placeholder="Оценка">
                            <textarea name="feedback" placeholder="Комментарий преподавателя"></textarea>
                            <button type="submit" name="submit_review">Сохранить проверку</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <h2>Моя сдача</h2>
        <?php if ($studentSubmission): ?>
            <section class="card">
                <span class="status-label">Статус: <?php echo e(taskStatusLabel($studentSubmission['status'])); ?></span>
                <p><?php echo nl2br(e($studentSubmission['content'])); ?></p>
                <?php if (!empty($studentSubmission['grade'])): ?>
                    <small>Оценка: <?php echo e($studentSubmission['grade']); ?></small>
                <?php endif; ?>
                <?php if (!empty($studentSubmission['feedback'])): ?>
                    <p><strong>Фидбек:</strong> <?php echo nl2br(e($studentSubmission['feedback'])); ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <textarea name="content" placeholder="Ответ, ссылка на работу или комментарий к сдаче" required><?php echo e($studentSubmission['content'] ?? ''); ?></textarea>
            <button type="submit" name="submit_work">Сдать работу</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
