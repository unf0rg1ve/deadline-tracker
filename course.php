<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';
require_once __DIR__ . '/models/CourseActivity.php';
require_once __DIR__ . '/models/CourseAnnouncement.php';
require_once __DIR__ . '/models/CourseMaterial.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/models/Task.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$courseId = (int) ($_GET['id'] ?? 0);
$course = Course::findForUser($courseId, (int) $_SESSION['user_id'], $_SESSION['role']);

if (!$course) {
    header('Location: courses.php');
    exit;
}

$announcementError = '';
if (isset($_POST['submit_announcement']) && ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin')) {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $announcementError = t('csrf_error');
    } elseif (strlen($title) < 3) {
        $announcementError = 'Заголовок должен быть не короче 3 символов.';
    } elseif (strlen($body) < 5) {
        $announcementError = 'Текст объявления должен быть не короче 5 символов.';
    } else {
        CourseAnnouncement::create($courseId, (int) $_SESSION['user_id'], $title, $body);
        Notification::notifyCourseStudents(
            $courseId,
            'announcement_created',
            'Новое объявление',
            $title,
            'course.php?id=' . $courseId,
            (int) $_SESSION['user_id']
        );
        flash('success', 'Объявление опубликовано.');
        header('Location: course.php?id=' . $courseId);
        exit;
    }
}

$tasks = Task::forCourse($courseId, (int) $_SESSION['user_id'], $_SESSION['role']);
$materials = CourseMaterial::forCourse($courseId);
$announcements = CourseAnnouncement::forCourse($courseId);
$activities = CourseActivity::forCourse($courseId);

include 'header.php';
?>

<main>
    <section class="page-hero">
        <span class="status-label">Курс</span>
        <h1><?php echo e($course['title']); ?></h1>
        <p><?php echo e($course['description']); ?></p>
        <?php if (!empty($course['group_name'])): ?>
            <small>Группы: <?php echo e($course['group_name']); ?></small>
        <?php endif; ?>
        <?php if (!empty($course['teacher_name'])): ?>
            <small>Преподаватели: <?php echo e($course['teacher_name']); ?></small>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-header">
            <h2>Лента курса</h2>
        </div>

        <?php if (empty($activities)): ?>
            <p>Активности пока нет.</p>
        <?php else: ?>
            <div class="activity-feed">
                <?php foreach ($activities as $activity): ?>
                    <?php preg_match('/./u', $activity['label'], $activityInitial); ?>
                    <a class="activity-item activity-<?php echo e($activity['type']); ?>" href="<?php echo e($activity['url']); ?>">
                        <span class="activity-dot"><?php echo e($activityInitial[0] ?? 'A'); ?></span>
                        <span class="activity-content">
                            <span class="activity-topline">
                                <strong><?php echo e($activity['label']); ?></strong>
                                <small><?php echo e($activity['created_at']); ?></small>
                            </span>
                            <span class="activity-title"><?php echo e($activity['title']); ?></span>
                            <span class="activity-body"><?php echo e(excerpt($activity['body'])); ?></span>
                            <?php if (!empty($activity['author_name'])): ?>
                                <small>Автор: <?php echo e($activity['author_name']); ?></small>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-header">
            <h2>Объявления</h2>
        </div>

        <?php if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
            <form method="POST" class="composer">
                <?php echo csrfField(); ?>
                <input type="text" name="title" placeholder="Заголовок объявления" required>
                <textarea name="body" placeholder="Сообщение для участников курса" required></textarea>
                <button type="submit" name="submit_announcement">Опубликовать</button>
            </form>
            <?php if ($announcementError): ?>
                <p class="error-msg"><?php echo e($announcementError); ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($announcements)): ?>
            <p>Объявлений пока нет.</p>
        <?php else: ?>
            <div class="timeline">
                <?php foreach ($announcements as $announcement): ?>
                    <article class="timeline-item">
                        <div class="timeline-meta">
                            <strong><?php echo e($announcement['author_name'] ?? 'Автор'); ?></strong>
                            <small><?php echo e($announcement['created_at']); ?></small>
                        </div>
                        <h3><?php echo e($announcement['title']); ?></h3>
                        <p><?php echo nl2br(e($announcement['body'])); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-header">
            <h2>Материалы курса</h2>
            <?php if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
                <a href="add_material.php?course_id=<?php echo (int) $course['id']; ?>">Добавить материал</a>
            <?php endif; ?>
        </div>

        <?php if (empty($materials)): ?>
            <p>Материалы пока не загружены.</p>
        <?php else: ?>
            <div class="resource-list">
                <?php foreach ($materials as $material): ?>
                    <a class="resource-item" href="download.php?type=material&id=<?php echo (int) $material['id']; ?>">
                        <span class="resource-icon">F</span>
                        <span>
                            <strong><?php echo e($material['title']); ?></strong>
                            <small><?php echo e($material['original_name']); ?> · <?php echo e(formatFileSize((int) $material['file_size'])); ?></small>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="section-block">
        <div class="section-header">
            <h2>Задания курса</h2>
            <?php if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
                <a href="add_task.php">Создать задание</a>
            <?php endif; ?>
        </div>

        <?php if (empty($tasks)): ?>
            <p>В этом курсе пока нет заданий.</p>
        <?php else: ?>
            <div class="cards-container">
                <?php foreach ($tasks as $task): ?>
                    <div class="card">
                        <h3><a href="task.php?id=<?php echo (int) $task['id']; ?>"><?php echo e($task['title']); ?></a></h3>
                        <p><?php echo e($task['description']); ?></p>
                        <small>Дедлайн: <?php echo e(date('d.m.Y', strtotime($task['deadline']))); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include 'footer.php'; ?>
