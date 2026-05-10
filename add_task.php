<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'student') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $deadline      = $_POST['deadline'] ?? '';
    $discipline_id = (int)($_POST['discipline_id'] ?? 0);
    $group_id      = (int)($_POST['group_id'] ?? 0);

    if (empty($title) || empty($deadline) || $discipline_id <= 0 || $group_id <= 0) {
        $_SESSION['error'] = "Заполните все обязательные поля!";
        header("Location: index.php");
        exit;
    }

    // Проверка для преподавателя
    if ($_SESSION['role'] === 'teacher') {
        $check = $pdo->prepare("SELECT COUNT(*) FROM teacher_disciplines WHERE teacher_id = ? AND discipline_id = ?");
        $check->execute([$_SESSION['user_id'], $discipline_id]);
        if ($check->fetchColumn() == 0) {
            $_SESSION['error'] = "Вы не привязаны к этой дисциплине!";
            header("Location: index.php");
            exit;
        }
    }

    $file_path = null;

    // Обработка загрузки файла
    if (isset($_FILES['task_file']) && $_FILES['task_file']['error'] === 0) {
        $upload_dir = 'uploads/tasks/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_ext = strtolower(pathinfo($_FILES['task_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','rar'];

        if (in_array($file_ext, $allowed)) {
            $new_name = time() . '_' . rand(1000,9999) . '.' . $file_ext;
            $target = $upload_dir . $new_name;

            if (move_uploaded_file($_FILES['task_file']['tmp_name'], $target)) {
                $file_path = $target;
            }
        } else {
            $_SESSION['error'] = "Недопустимый формат файла!";
            header("Location: index.php");
            exit;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO tasks (title, description, deadline, discipline_id, teacher_id, group_id, file_path, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'в процессе')
    ");

    $success = $stmt->execute([
        $title, $description, $deadline, $discipline_id, 
        $_SESSION['user_id'], $group_id, $file_path
    ]);

    if ($success) {
        $_SESSION['success'] = "Задание успешно добавлено!";
    } else {
        $_SESSION['error'] = "Ошибка при добавлении задания.";
    }
}

header("Location: index.php");
exit;
?>