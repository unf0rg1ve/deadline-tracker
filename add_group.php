<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/StudentGroup.php';

requireRole('admin');

$error = '';

if (isset($_POST['submit'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($name) < 2) {
        $error = 'Название группы должно быть не короче 2 символов.';
    } else {
        StudentGroup::create($name, $description);
        flash('success', t('group_created'));
        header('Location: groups.php');
        exit;
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Новая группа</h2>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <input type="text" name="name" placeholder="Название группы" required>
            <textarea name="description" placeholder="Описание группы"></textarea>
            <button type="submit" name="submit">Создать группу</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
