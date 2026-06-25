<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class TaskAttachment
{
    public static function forTask(int $taskId): array
    {
        $statement = getDatabase()->prepare('
            SELECT task_attachments.*, users.full_name AS uploader_name
            FROM task_attachments
            LEFT JOIN users ON users.id = task_attachments.uploaded_by
            WHERE task_attachments.task_id = :task_id
            ORDER BY task_attachments.created_at DESC
        ');
        $statement->execute(['task_id' => $taskId]);

        return $statement->fetchAll();
    }

    public static function create(int $taskId, int $uploadedBy, string $title, array $fileData): int
    {
        $statement = getDatabase()->prepare('
            INSERT INTO task_attachments (
                task_id, uploaded_by, title, original_name, stored_name, mime_type, file_size
            )
            VALUES (
                :task_id, :uploaded_by, :title, :original_name, :stored_name, :mime_type, :file_size
            )
        ');
        $statement->execute([
            'task_id' => $taskId,
            'uploaded_by' => $uploadedBy,
            'title' => trim($title),
            'original_name' => $fileData['original_name'],
            'stored_name' => $fileData['stored_name'],
            'mime_type' => $fileData['mime_type'],
            'file_size' => $fileData['file_size'],
        ]);

        return (int) getDatabase()->lastInsertId();
    }

    public static function find(int $attachmentId): ?array
    {
        $statement = getDatabase()->prepare('SELECT * FROM task_attachments WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $attachmentId]);

        return $statement->fetch() ?: null;
    }
}
