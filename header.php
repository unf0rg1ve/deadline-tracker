<?php
session_start();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deadline Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
    <div class="header-container">
        <!-- Логотип + Навигация по центру -->
        <div class="header-content">
            <a href="index.php" class="logo-link">
                <img src="logo.png" alt="Deadline Tracker" class="logo">
            </a>
            
            <nav>
                <ul>
                    <li><a href="index.php">Главная</a></li>
                    <li><a href="about.php">О нас</a></li>
                    <li><a href="contact.php">Контакты</a></li>
                    
                    <?php if (isset($_SESSION['role'])): ?>
                        <?php if ($_SESSION['role'] === 'teacher'): ?>
                            <li><a href="teacher_page.php">Мои курсы</a></li>
                        <?php endif; ?>

                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="admin.php" style="color: #e74c3c;">Админ-панель</a></li>
                        <?php endif; ?>

                        <li><a href="logout.php">Выйти (<?php echo htmlspecialchars($_SESSION['name']); ?>)</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Войти</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</header>
    <hr>