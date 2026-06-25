<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Course.php';
require_once __DIR__ . '/models/CourseMaterial.php';
require_once __DIR__ . '/models/Task.php';
require_once __DIR__ . '/models/TaskAttachment.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$type = $_GET['type'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
$fileRecord = null;

if ($type === 'material') {
    $material = CourseMaterial::find($id);
    if ($material && Course::findForUser((int) $material['course_id'], (int) $_SESSION['user_id'], $_SESSION['role'])) {
        $fileRecord = $material;
    }
} elseif ($type === 'attachment') {
    $attachment = TaskAttachment::find($id);
    if ($attachment && Task::findForUser((int) $attachment['task_id'], (int) $_SESSION['user_id'], $_SESSION['role'])) {
        $fileRecord = $attachment;
    }
}

if (!$fileRecord) {
    header('Location: index.php');
    exit;
}

$filePath = uploadBaseDir() . '/' . $fileRecord['stored_name'];
if (!is_file($filePath)) {
    flash('error', 'Файл не найден.');
    header('Location: index.php');
    exit;
}

header('Content-Type: ' . $fileRecord['mime_type']);
header('Content-Length: ' . filesize($filePath));
$downloadName = str_replace(["\r", "\n", '"'], '', basename($fileRecord['original_name']));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
