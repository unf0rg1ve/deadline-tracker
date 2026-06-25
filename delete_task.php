<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Task.php';

requireAnyRole(['teacher', 'admin']);

$taskId = (int) ($_GET['id'] ?? $_POST['task_id'] ?? 0);
$task = Task::findForUser($taskId, (int) $_SESSION['user_id'], $_SESSION['role']);

if (!$task) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['confirm_delete']) && verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    Task::delete($taskId);
    flash('success', t('task_deleted'));
    header('Location: index.php');
    exit;
} elseif (isset($_POST['confirm_delete'])) {
    $error = t('csrf_error');
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Удалить задание?</h2>
        <p><?php echo e($task['title']); ?></p>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
            <button type="submit" name="confirm_delete" class="button-danger">Удалить</button>
        </form>
        <?php if (!empty($error)): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
        <p><a href="task.php?id=<?php echo (int) $task['id']; ?>">Отмена</a></p>
    </div>
</main>

<?php include 'footer.php'; ?>
