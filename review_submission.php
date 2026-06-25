<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Notification.php';
require_once __DIR__ . '/models/Submission.php';
require_once __DIR__ . '/models/Task.php';

requireAnyRole(['teacher', 'admin']);

$taskId = (int) ($_POST['task_id'] ?? 0);
$submissionId = (int) ($_POST['submission_id'] ?? 0);
$task = Task::findForUser($taskId, (int) $_SESSION['user_id'], $_SESSION['role']);

if (!$task || !isset($_POST['submit_review']) || !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flash('error', t('csrf_error'));
    }
    header('Location: ' . ($task ? 'task.php?id=' . $taskId : 'index.php'));
    exit;
}

$status = $_POST['status'] ?? 'reviewed';
if (!in_array($status, ['reviewed', 'needs_revision'], true)) {
    $status = 'reviewed';
}

Submission::review(
    $submissionId,
    $taskId,
    $status,
    $_POST['grade'] ?? '',
    $_POST['feedback'] ?? ''
);

$submission = Submission::findById($submissionId);
if ($submission) {
    Notification::create(
        (int) $submission['user_id'],
        'work_reviewed',
        'Работа проверена',
        $task['title'],
        'task.php?id=' . $taskId
    );
}

flash('success', t('review_saved'));
header('Location: task.php?id=' . $taskId);
exit;
