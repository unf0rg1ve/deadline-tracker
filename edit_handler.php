<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['edit_type'] ?? '';
    $id   = (int)($_POST['edit_id'] ?? 0);
    $name = trim($_POST['new_name'] ?? '');

    if ($id > 0 && !empty($name)) {
        if ($type === 'group') {
            $pdo->prepare("UPDATE `groups` SET name = ? WHERE id = ?")->execute([$name, $id]);
            $_SESSION['success'] = "Группа обновлена!";
        } 
        elseif ($type === 'discipline') {
            $pdo->prepare("UPDATE disciplines SET name = ? WHERE id = ?")->execute([$name, $id]);
            $_SESSION['success'] = "Дисциплина обновлена!";
        } 
        elseif ($type === 'user') {
            $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$name, $id]);
            $_SESSION['success'] = "Пользователь обновлён!";
        }
    } else {
        $_SESSION['error'] = "Ошибка при редактировании.";
    }
}

header("Location: admin.php");
exit;
?>