<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class TaskComment
{
    public static function forTask(int $taskId): array
    {
        $statement = getDatabase()->prepare('
            SELECT task_comments.*, users.full_name AS author_name, users.role AS author_role
            FROM task_comments
            LEFT JOIN users ON users.id = task_comments.author_id
            WHERE task_comments.task_id = :task_id
            ORDER BY task_comments.created_at ASC
        ');
        $statement->execute(['task_id' => $taskId]);

        return $statement->fetchAll();
    }

    public static function create(int $taskId, int $authorId, string $body): int
    {
        $statement = getDatabase()->prepare('
            INSERT INTO task_comments (task_id, author_id, body)
            VALUES (:task_id, :author_id, :body)
        ');
        $statement->execute([
            'task_id' => $taskId,
            'author_id' => $authorId,
            'body' => trim($body),
        ]);

        return (int) getDatabase()->lastInsertId();
    }
}
