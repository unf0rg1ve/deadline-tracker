<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/models/Task.php';

requireAnyRole(['teacher', 'admin']);

$error = '';
$courses = Course::forUser((int) $_SESSION['user_id'], $_SESSION['role']);

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
        $taskId = Task::create($title, $description, $deadline, $courseId, (int) $_SESSION['user_id']);
        Notification::notifyCourseStudents(
            $courseId,
            'task_created',
            'Новое задание',
            $title,
            'task.php?id=' . $taskId,
            (int) $_SESSION['user_id']
        );
        flash('success', t('task_created'));
        header('Location: index.php');
        exit;
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Новое задание</h2>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <select name="course_id" required>
                <option value="">Выберите курс</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo (int) $course['id']; ?>"><?php echo e($course['title']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="title" placeholder="Название" required>
            <textarea name="description" placeholder="Описание задания" required></textarea>
            <input type="date" name="deadline" required>
            <button type="submit" name="submit">Создать</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
