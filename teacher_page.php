<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';
requireAnyRole(['teacher', 'admin']);

$courses = Course::forUser((int) $_SESSION['user_id'], $_SESSION['role']);

include 'header.php';
?>

<main>
    <h1>Мои курсы</h1>

    <?php if (empty($courses)): ?>
        <p>Пока нет доступных курсов.</p>
    <?php else: ?>
        <div class="cards-container">
            <?php foreach ($courses as $course): ?>
                <div class="card">
                    <h3><a href="course.php?id=<?php echo (int) $course['id']; ?>"><?php echo e($course['title']); ?></a></h3>
                    <p><?php echo e($course['description']); ?></p>
                    <?php if (!empty($course['group_name'])): ?>
                        <small>Группа: <?php echo e($course['group_name']); ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p><a href="add_course.php">Создать курс</a></p>
    <p><a href="add_task.php">Создать задание</a></p>
</main>

<?php include 'footer.php'; ?>
