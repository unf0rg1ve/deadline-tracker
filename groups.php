<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/StudentGroup.php';

requireRole('admin');

$groups = StudentGroup::all();

include 'header.php';
?>

<main>
    <h1>Учебные группы</h1>

    <?php if (empty($groups)): ?>
        <p>Пока нет учебных групп.</p>
    <?php else: ?>
        <div class="cards-container">
            <?php foreach ($groups as $group): ?>
                <?php $students = StudentGroup::students((int) $group['id']); ?>
                <div class="card">
                    <h3><?php echo e($group['name']); ?></h3>
                    <p><?php echo e($group['description']); ?></p>
                    <small>Студентов: <?php echo (int) $group['students_count']; ?></small>
                    <?php if (!empty($students)): ?>
                        <ul>
                            <?php foreach ($students as $student): ?>
                                <li><?php echo e($student['full_name']); ?> <small><?php echo e($student['username']); ?></small></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <p>
        <a href="add_group.php">Создать группу</a>
        <a href="students.php">Управлять студентами</a>
    </p>
</main>

<?php include 'footer.php'; ?>
