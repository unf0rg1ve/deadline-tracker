<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Task.php';
require_once __DIR__ . '/models/TaskAttachment.php';

requireAnyRole(['teacher', 'admin']);

$taskId = (int) ($_GET['task_id'] ?? $_POST['task_id'] ?? 0);
$task = Task::findForUser($taskId, (int) $_SESSION['user_id'], $_SESSION['role']);

if (!$task) {
    header('Location: index.php');
    exit;
}

$error = '';

if (isset($_POST['submit'])) {
    $title = trim($_POST['title'] ?? '');
    $file = $_FILES['attachment_file'] ?? null;

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($title) < 3) {
        $error = 'Название файла должно быть не короче 3 символов.';
    } elseif (!$file || ($uploadError = validateUploadedFile($file))) {
        $error = $uploadError ?? 'Выберите файл.';
    } else {
        try {
            $fileData = storeUploadedFile($file, 'task_attachments');
            TaskAttachment::create($taskId, (int) $_SESSION['user_id'], $title, $fileData);
            flash('success', 'Файл задания загружен.');
            header('Location: task.php?id=' . $taskId);
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Добавить файл к заданию</h2>
        <p><?php echo e($task['title']); ?></p>
        <form method="POST" enctype="multipart/form-data" class="login-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="task_id" value="<?php echo (int) $task['id']; ?>">
            <input type="text" name="title" placeholder="Название файла" required>
            <input type="file" name="attachment_file" required>
            <button type="submit" name="submit">Загрузить</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
