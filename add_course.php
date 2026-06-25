<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';
require_once __DIR__ . '/models/StudentGroup.php';
require_once __DIR__ . '/models/User.php';

requireAnyRole(['teacher', 'admin']);

$error = '';
$groups = StudentGroup::all();
$teachers = User::allByRole('teacher');

if (isset($_POST['submit'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $groupIds = array_map('intval', (array) ($_POST['group_ids'] ?? []));
    $teacherIds = $_SESSION['role'] === 'admin'
        ? array_map('intval', (array) ($_POST['teacher_ids'] ?? []))
        : [(int) $_SESSION['user_id']];
    $allowedGroupIds = array_map(static fn(array $group): int => (int) $group['id'], $groups);
    $allowedTeacherIds = array_map(static fn(array $teacher): int => (int) $teacher['id'], $teachers);
    $groupIds = array_values(array_intersect($groupIds, $allowedGroupIds));
    $teacherIds = $_SESSION['role'] === 'admin'
        ? array_values(array_intersect($teacherIds, $allowedTeacherIds))
        : $teacherIds;

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($title) < 3) {
        $error = 'Название курса должно быть не короче 3 символов.';
    } elseif (empty($groupIds)) {
        $error = 'Выберите хотя бы одну группу.';
    } elseif (empty($teacherIds)) {
        $error = 'Выберите хотя бы одного преподавателя.';
    } else {
        Course::create($title, $description, $teacherIds, $groupIds);
        flash('success', t('course_created'));
        header('Location: courses.php');
        exit;
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Новый курс</h2>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <input type="text" name="title" placeholder="Название курса" required>
            <textarea name="description" placeholder="Описание курса"></textarea>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <label>Преподаватели</label>
                <div class="choice-grid">
                    <?php foreach ($teachers as $teacher): ?>
                        <label class="choice-item">
                            <input type="checkbox" name="teacher_ids[]" value="<?php echo (int) $teacher['id']; ?>">
                            <span><?php echo e($teacher['full_name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <label>Группы</label>
            <div class="choice-grid">
                <?php foreach ($groups as $group): ?>
                    <label class="choice-item">
                        <input type="checkbox" name="group_ids[]" value="<?php echo (int) $group['id']; ?>">
                        <span><?php echo e($group['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" name="submit">Создать курс</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
