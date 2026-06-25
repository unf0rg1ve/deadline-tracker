<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';
require_once __DIR__ . '/models/Task.php';

requireAnyRole(['teacher', 'admin']);

$taskId = (int) ($_GET['id'] ?? $_POST['task_id'] ?? 0);
$task = Task::findForUser($taskId, (int) $_SESSION['user_id'], $_SESSION['role']);

if (!$task) {
    header('Location: index.php');
    exit;
}

$courses = Course::forUser((int) $_SESSION['user_id'], $_SESSION['role']);
$error = '';

if (isset($_POST['submit'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $deadline = $_POST['deadline'] ?? '';
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $allowedCourseIds = array_map(static fn(array $course): int => (int) $course['id'], $courses);

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($title) < 3) {
        $error = 'Название должно быть не короче 3 символов.';
    } elseif (strlen($description) < 5) {
        $error = 'Описание должно быть не короче 5 символов.';
    } elseif (!$deadline || strtotime($deadline) === false) {
        $error = 'Укажите корректный дедлайн.';
    } elseif (!in_array($courseId, $allowedCourseIds, true)) {
        $error = 'Выберите доступный курс.';
    } else {
        Task::update($taskId, $title, $description, $deadline, $courseId);
        flash('success', t('task_updated'));
        header('Location: task.php?id=' . $taskId);
        exit;
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Редактировать задание</h2>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
            <select name="course_id" required>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo (int) $course['id']; ?>" <?php echo ((int) $course['id'] === (int) $task['course_id']) ? 'selected' : ''; ?>>
                        <?php echo e($course['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="title" value="<?php echo e($task['title']); ?>" placeholder="Название" required>
            <textarea name="description" placeholder="Описание задания" required><?php echo e($task['description']); ?></textarea>
            <input type="date" name="deadline" value="<?php echo e($task['deadline']); ?>" required>
            <button type="submit" name="submit">Сохранить</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
