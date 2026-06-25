<?php
require_once __DIR__ . '/bootstrap.php';
requireRole('admin');

include 'header.php';
?>

<main>
    <h1>Панель администратора</h1>
    <p>Вы вошли как: <strong><?php echo e($_SESSION['name']); ?></strong> (Роль: <?php echo e($_SESSION['role']); ?>)</p>
    
    <section class="card">
        <span class="status-label">Admin</span>
        <h3>Управление системой</h3>
        <ul>
            <li><a href="groups.php">Управление учебными группами</a></li>
            <li><a href="students.php">Управление студентами и привязкой к группам</a></li>
            <li><a href="courses.php">Управление курсами</a></li>
            <li><a href="add_task.php">Создание заданий</a></li>
        </ul>
    </section>
    
    <p><a href="index.php">← Вернуться на главную</a></p>
</main>

<?php include 'footer.php'; ?>
