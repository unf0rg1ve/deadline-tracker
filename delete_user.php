<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Защита: не даём удалить самого админа
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = "Нельзя удалить самого себя!";
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        $_SESSION['success'] = "Пользователь успешно удалён!";
    }
}

header("Location: admin.php");
exit;
?>