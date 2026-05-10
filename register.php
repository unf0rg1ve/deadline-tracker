<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $role        = $_POST['role'] ?? 'student';
    $group_id    = (int)($_POST['group_id'] ?? 0);

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Заполните все обязательные поля!";
    } elseif ($role === 'student' && $group_id <= 0) {
        $error = "Студент должен выбрать группу!";
    } else {
        $userModel = new User($pdo);
        
        // Проверяем, существует ли email
        if ($userModel->findByEmail($email)) {
            $error = "Пользователь с таким email уже существует!";
        } else {
            $group_name = null;
            if ($role === 'student' && $group_id > 0) {
                $g = $pdo->prepare("SELECT name FROM groups WHERE id = ?");
                $g->execute([$group_id]);
                $group_name = $g->fetchColumn();
            }

            $created = $userModel->create($name, $email, $password, $role, $group_name);

            if ($created) {
                $success = "Регистрация прошла успешно! Теперь вы можете войти.";
            } else {
                $error = "Ошибка при регистрации. Попробуйте ещё раз.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация — Deadline Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>

<main class="login-container">
    <div class="login-card">
        <h2>Регистрация</h2>
        
        <?php if ($success): ?>
            <p style="color:green; font-weight:bold;"><?= $success ?></p>
            <p><a href="login.php">→ Перейти ко входу</a></p>
        <?php else: ?>
            <form method="POST" class="login-form">
                <input type="text" name="name" placeholder="ФИО" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Пароль" required>

                <select name="role" id="roleSelect" required style="width:100%; padding:10px; margin:8px 0;">
                    <option value="student">Студент</option>
                    <option value="teacher">Преподаватель</option>
                </select>

                <!-- Поле группы только для студентов -->
                <div id="groupField">
                    <select name="group_id" style="width:100%; padding:10px; margin:8px 0;">
                        <option value="">Выберите группу</option>
                        <?php
                        $groups = $pdo->query("SELECT id, name FROM groups ORDER BY name")->fetchAll();
                        foreach ($groups as $g):
                        ?>
                            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">Зарегистрироваться</button>
            </form>

            <?php if ($error): ?>
                <p class="error-msg"><?= $error ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
    </div>
</main>

<?php include 'footer.php'; ?>

<script>
// Показываем/скрываем поле группы в зависимости от роли
document.getElementById('roleSelect').addEventListener('change', function() {
    const groupField = document.getElementById('groupField');
    groupField.style.display = (this.value === 'student') ? 'block' : 'none';
});
</script>