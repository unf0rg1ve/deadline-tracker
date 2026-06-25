<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/User.php';

$error = '';

if (isset($_POST['submit'])) {
    $user_login = trim($_POST['user'] ?? '');
    $user_pass  = $_POST['pass'] ?? '';
    $user = verifyCsrfToken($_POST['csrf_token'] ?? null)
        ? User::authenticate($user_login, $user_pass)
        : null;

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif ($user) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        flash('success', t('login_success'));
        
        if ($_SESSION['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Неверный логин или пароль!";
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Вход в систему</h2>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <input type="text" name="user" placeholder="Логин" required>
            <input type="password" name="pass" placeholder="Пароль" required>
            <button type="submit" name="submit">Войти</button>
        </form>

        <?php if($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
        
        <div class="hints">
            <p>Тестовые доступы:</p>
            <span>stud/111, prep/222, admin/333</span>
        </div>
        <p class="register-link"><a href="register.php">Создать аккаунт студента</a></p>
    </div>
</main>

<?php include 'footer.php'; ?>
