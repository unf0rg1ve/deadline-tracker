<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';
require_once __DIR__ . '/models/StudentGroup.php';
require_once __DIR__ . '/models/User.php';

requireRole('admin');

$courseId = (int) ($_GET['id'] ?? $_POST['course_id'] ?? 0);
$course = Course::findForUser($courseId, (int) $_SESSION['user_id'], $_SESSION['role']);

if (!$course) {
    header('Location: courses.php');
    exit;
}

$groups = StudentGroup::all();
$teachers = User::allByRole('teacher');
$selectedGroupIds = Course::groupIds($courseId);
$selectedTeacherIds = Course::teacherIds($courseId);
$error = '';

if (isset($_POST['submit'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $groupIds = array_map('intval', (array) ($_POST['group_ids'] ?? []));
    $teacherIds = array_map('intval', (array) ($_POST['teacher_ids'] ?? []));
    $allowedGroupIds = array_map(static fn(array $group): int => (int) $group['id'], $groups);
    $allowedTeacherIds = array_map(static fn(array $teacher): int => (int) $teacher['id'], $teachers);
    $groupIds = array_values(array_intersect($groupIds, $allowedGroupIds));
    $teacherIds = array_values(array_intersect($teacherIds, $allowedTeacherIds));

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($title) < 3) {
        $error = 'Название курса должно быть не короче 3 символов.';
    } elseif (empty($groupIds)) {
        $error = 'Выберите хотя бы одну группу.';
    } elseif (empty($teacherIds)) {
        $error = 'Выберите хотя бы одного преподавателя.';
    } else {
        Course::update($courseId, $title, $description, $teacherIds, $groupIds);
        flash('success', 'Курс обновлён.');
        header('Location: courses.php');
        exit;
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Редактирование курса</h2>
        <form method="POST" class="login-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="course_id" value="<?php echo (int) $course['id']; ?>">
            <input type="text" name="title" value="<?php echo e($course['title']); ?>" placeholder="Название курса" required>
            <textarea name="description" placeholder="Описание курса"><?php echo e($course['description']); ?></textarea>

            <label>Преподаватели</label>
            <div class="choice-grid">
                <?php foreach ($teachers as $teacher): ?>
                    <label class="choice-item">
                        <input type="checkbox" name="teacher_ids[]" value="<?php echo (int) $teacher['id']; ?>" <?php echo in_array((int) $teacher['id'], $selectedTeacherIds, true) ? 'checked' : ''; ?>>
                        <span><?php echo e($teacher['full_name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label>Группы</label>
            <div class="choice-grid">
                <?php foreach ($groups as $group): ?>
                    <label class="choice-item">
                        <input type="checkbox" name="group_ids[]" value="<?php echo (int) $group['id']; ?>" <?php echo in_array((int) $group['id'], $selectedGroupIds, true) ? 'checked' : ''; ?>>
                        <span><?php echo e($group['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <button type="submit" name="submit">Сохранить изменения</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
