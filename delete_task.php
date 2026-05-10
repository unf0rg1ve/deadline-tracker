<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Нет доступа']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
    $task_id = (int)$_POST['task_id'];

    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    if ($stmt->execute([$task_id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Не удалось удалить']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Неверный запрос']);
}
?>