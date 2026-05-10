<?php
require_once 'config/config.php';
require_once 'gemini_api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id     = $_POST['task_id'] ?? '';
    $title       = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $deadline    = $_POST['deadline'] ?? '';

    if (empty($title)) {
        echo "Ошибка: название задания отсутствует.";
        exit;
    }

    $gemini = new GeminiAPI(GEMINI_API_KEY);
    $result = $gemini->analyzeTask($title, $description, $deadline);

    echo $result;
} else {
    echo "Неверный запрос.";
}
?>