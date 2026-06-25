<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$courses = Course::forUser((int) $_SESSION['user_id'], $_SESSION['role']);

include 'header.php';
?>

<main>
    <h1>Курсы</h1>

    <?php if (empty($courses)): ?>
        <p>Пока нет доступных курсов.</p>
    <?php else: ?>
        <div class="cards-container">
            <?php foreach ($courses as $course): ?>
                <div class="card">
                    <h3><a href="course.php?id=<?php echo (int) $course['id']; ?>"><?php echo e($course['title']); ?></a></h3>
                    <p><?php echo e($course['description']); ?></p>
                    <?php if (!empty($course['group_name'])): ?>
                        <small>Группы: <?php echo e($course['group_name']); ?></small>
                    <?php endif; ?>
                    <?php if (!empty($course['teacher_name'])): ?>
                        <small>Преподаватели: <?php echo e($course['teacher_name']); ?></small>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <p><a href="edit_course.php?id=<?php echo (int) $course['id']; ?>">Редактировать курс</a></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
        <p><a href="add_course.php">Создать курс</a></p>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
