<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Submission
{
    public static function findForTaskAndUser(int $taskId, int $userId): ?array
    {
        $statement = getDatabase()->prepare('
            SELECT task_submissions.*, users.full_name, users.username
            FROM task_submissions
            INNER JOIN users ON users.id = task_submissions.user_id
            WHERE task_submissions.task_id = :task_id AND task_submissions.user_id = :user_id
            LIMIT 1
        ');
        $statement->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
        ]);

        return $statement->fetch() ?: null;
    }

    public static function forTask(int $taskId): array
    {
        $statement = getDatabase()->prepare('
            SELECT task_submissions.*, users.full_name, users.username
            FROM task_submissions
            INNER JOIN users ON users.id = task_submissions.user_id
            WHERE task_submissions.task_id = :task_id
            ORDER BY task_submissions.submitted_at DESC
        ');
        $statement->execute(['task_id' => $taskId]);

        return $statement->fetchAll();
    }

    public static function findById(int $submissionId): ?array
    {
        $statement = getDatabase()->prepare('SELECT * FROM task_submissions WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $submissionId]);

        return $statement->fetch() ?: null;
    }

    public static function save(int $taskId, int $userId, string $content): void
    {
        if (self::findForTaskAndUser($taskId, $userId)) {
            $statement = getDatabase()->prepare('
                UPDATE task_submissions
                SET content = :content,
                    status = "submitted",
                    submitted_at = CURRENT_TIMESTAMP,
                    reviewed_at = NULL,
                    grade = NULL,
                    feedback = NULL
                WHERE task_id = :task_id AND user_id = :user_id
            ');
            $statement->execute([
                'task_id' => $taskId,
                'user_id' => $userId,
                'content' => trim($content),
            ]);

            return;
        }

        $statement = getDatabase()->prepare('
            INSERT INTO task_submissions (task_id, user_id, content)
            VALUES (:task_id, :user_id, :content)
        ');
        $statement->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
            'content' => trim($content),
        ]);
    }

    public static function review(int $submissionId, int $taskId, string $status, string $grade, string $feedback): void
    {
        $statement = getDatabase()->prepare('
            UPDATE task_submissions
            SET status = :status,
                grade = :grade,
                feedback = :feedback,
                reviewed_at = CURRENT_TIMESTAMP
            WHERE id = :submission_id AND task_id = :task_id
        ');
        $statement->execute([
            'submission_id' => $submissionId,
            'task_id' => $taskId,
            'status' => $status,
            'grade' => trim($grade),
            'feedback' => trim($feedback),
        ]);
    }
}
