<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/StudentGroup.php';

requireRole('admin');

$groups = StudentGroup::all();
$students = StudentGroup::allStudentsWithGroups();
$error = '';

if (isset($_POST['change_group'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $groupId = (int) ($_POST['group_id'] ?? 0);
    $allowedGroupIds = array_map(static fn(array $group): int => (int) $group['id'], $groups);

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif ($userId <= 0 || !in_array($groupId, $allowedGroupIds, true)) {
        $error = 'Выберите студента и группу.';
    } else {
        StudentGroup::setStudentGroup($groupId, $userId);
        flash('success', 'Группа студента обновлена.');
        header('Location: students.php');
        exit;
    }
}

include 'header.php';
?>

<main>
    <div class="section-header">
        <h1>Студенты</h1>
        <a href="add_student.php">Создать студента</a>
    </div>

    <?php if ($error): ?>
        <p class="error-msg"><?php echo e($error); ?></p>
    <?php endif; ?>

    <?php if (empty($students)): ?>
        <p>Студентов пока нет.</p>
    <?php else: ?>
        <div class="cards-container">
            <?php foreach ($students as $student): ?>
                <div class="card">
                    <span class="status-label"><?php echo e($student['group_name'] ?? 'Без группы'); ?></span>
                    <h3><?php echo e($student['full_name']); ?></h3>
                    <p>Логин: <?php echo e($student['username']); ?></p>

                    <?php if (!empty($groups)): ?>
                        <form method="POST" class="login-form">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="user_id" value="<?php echo (int) $student['id']; ?>">
                            <select name="group_id" required>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo (int) $group['id']; ?>" <?php echo (int) ($student['group_id'] ?? 0) === (int) $group['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($group['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="change_group">Назначить группу</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
