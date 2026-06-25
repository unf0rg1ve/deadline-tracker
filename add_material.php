<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';
require_once __DIR__ . '/models/CourseMaterial.php';
require_once __DIR__ . '/models/Notification.php';

requireAnyRole(['teacher', 'admin']);

$courseId = (int) ($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$course = Course::findForUser($courseId, (int) $_SESSION['user_id'], $_SESSION['role']);

if (!$course) {
    header('Location: courses.php');
    exit;
}

$error = '';

if (isset($_POST['submit'])) {
    $title = trim($_POST['title'] ?? '');
    $file = $_FILES['material_file'] ?? null;

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = t('csrf_error');
    } elseif (strlen($title) < 3) {
        $error = 'Название материала должно быть не короче 3 символов.';
    } elseif (!$file || ($uploadError = validateUploadedFile($file))) {
        $error = $uploadError ?? 'Выберите файл.';
    } else {
        try {
            $fileData = storeUploadedFile($file, 'course_materials');
            $materialId = CourseMaterial::create($courseId, (int) $_SESSION['user_id'], $title, $fileData);
            Notification::notifyCourseStudents(
                $courseId,
                'material_created',
                'Новый материал',
                $title,
                'download.php?type=material&id=' . $materialId,
                (int) $_SESSION['user_id']
            );
            flash('success', 'Материал загружен.');
            header('Location: course.php?id=' . $courseId);
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

include 'header.php';
?>

<main class="login-container">
    <div class="login-card">
        <h2>Добавить материал</h2>
        <p><?php echo e($course['title']); ?></p>
        <form method="POST" enctype="multipart/form-data" class="login-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="course_id" value="<?php echo (int) $course['id']; ?>">
            <input type="text" name="title" placeholder="Название материала" required>
            <input type="file" name="material_file" required>
            <button type="submit" name="submit">Загрузить</button>
        </form>

        <?php if ($error): ?>
            <p class="error-msg"><?php echo e($error); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>
