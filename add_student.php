<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/StudentGroup.php';
require_once __DIR__ . '/models/User.php';

requireRole('admin');

$groups = StudentGroup::all();
$error = '';

if (isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $groupId = (int) ($_POST['group_id'] ?? 0);
    $allowedGroupIds = array_map(static fn(array $group): int => (int) $group['id'], $groups);

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($username) < 3) {
        $error = 'Логин должен быть не короче 3 символов.';
    } elseif (strlen($fullName) < 2) {
        $error = 'Укажите имя студента.';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не короче 6 символов.';
    } elseif (User::findByUsername($username)) {
        $error = 'Пользователь с таким логином уже существует.';
    } elseif (!in_array($groupId, $allowedGroupIds, true)) {
        $error = 'Выберите учебную группу.';
    } else {
        $userId = User::create($username, $password, $fullName, 'student');
        StudentGroup::setStudentGroup($groupId, $userId);
        flash('success', 'Студент создан и добавлен в группу.');
        header('Location: students.php');
        exit;
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Новый студент</h2>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <input type="text" name="username" placeholder="Логин" required>
            <input type="text" name="full_name" placeholder="Имя и фамилия" required>
            <select name="group_id" required>
                <option value="">Выберите группу</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?php echo (int) $group['id']; ?>"><?php echo e($group['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit" name="submit">Создать студента</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
