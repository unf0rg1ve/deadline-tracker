<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/StudentGroup.php';
require_once __DIR__ . '/models/User.php';

$error = '';
$groups = StudentGroup::all();

if (isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $groupId = (int) ($_POST['group_id'] ?? 0);
    $allowedGroupIds = array_map(static fn(array $group): int => (int) $group['id'], $groups);

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($username) < 3) {
        $error = 'Логин должен быть не короче 3 символов.';
    } elseif (strlen($fullName) < 2) {
        $error = 'Укажите имя.';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не короче 6 символов.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Пароли не совпадают.';
    } elseif (User::findByUsername($username)) {
        $error = 'Пользователь с таким логином уже существует.';
    } elseif (!empty($groups) && !in_array($groupId, $allowedGroupIds, true)) {
        $error = 'Выберите учебную группу.';
    } else {
        $userId = User::create($username, $password, $fullName, 'student');
        if ($groupId) {
            StudentGroup::addStudent($groupId, $userId);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = 'student';
        $_SESSION['name'] = $username;
        $_SESSION['full_name'] = $fullName;
        flash('success', t('register_success'));
        header('Location: index.php');
        exit;
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Регистрация студента</h2>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <input type="text" name="username" placeholder="Логин" required>
            <input type="text" name="full_name" placeholder="Имя и фамилия" required>
            <?php if (!empty($groups)): ?>
                <select name="group_id" required>
                    <option value="">Выберите группу</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo (int) $group['id']; ?>">
                            <?php echo e($group['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <input type="password" name="password" placeholder="Пароль" required>
            <input type="password" name="password_confirm" placeholder="Повторите пароль" required>
            <button type="submit" name="submit">Создать аккаунт</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>

        <p class="register-link"><a href="login.php">Уже есть аккаунт?</a></p>
    </div>
</main>

<?php include 'footer.php'; ?>
