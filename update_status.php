<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status  = $_POST['status'] ?? '';

    if ($task_id > 0 && in_array($status, ['в процессе', 'сдана', 'проверена'])) {
        $student_file = null;

        // Если студент сдаёт задание и прикрепил файл
        if ($status === 'сдана' && isset($_FILES['student_file']) && $_FILES['student_file']['error'] === 0) {
            $upload_dir = 'uploads/reports/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $file_ext = strtolower(pathinfo($_FILES['student_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf','doc','docx','jpg','png','zip','rar'];

            if (in_array($file_ext, $allowed)) {
                $new_name = time() . '_report_' . rand(1000,9999) . '.' . $file_ext;
                $target = $upload_dir . $new_name;

                if (move_uploaded_file($_FILES['student_file']['tmp_name'], $target)) {
                    $student_file = $target;
                }
            }
        }

        $sql = "UPDATE tasks SET status = ?";
        $params = [$status];

        if ($student_file) {
            $sql .= ", student_file_path = ?";
            $params[] = $student_file;
        }

        $sql .= " WHERE id = ?";
        $params[] = $task_id;

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $_SESSION['success'] = "Статус обновлён!";
        } else {
            $_SESSION['error'] = "Ошибка при обновлении.";
        }
    }
}

header("Location: index.php");
exit;
?>