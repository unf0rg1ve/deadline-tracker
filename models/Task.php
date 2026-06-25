<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Task
{
    public static function forUser(int $userId, string $role): array
    {
        if ($role === 'admin') {
            $statement = getDatabase()->query(self::baseQuery() . ' ORDER BY deadline ASC');

            return $statement->fetchAll();
        }

        if ($role === 'teacher') {
            $statement = getDatabase()->prepare(
                self::baseQuery() . '
                WHERE courses.teacher_id = :user_id_legacy
                   OR EXISTS (
                        SELECT 1
                        FROM course_teachers
                        WHERE course_teachers.course_id = courses.id
                          AND course_teachers.user_id = :user_id_access
                   )
                ORDER BY deadline ASC'
            );
            $statement->execute([
                'user_id_legacy' => $userId,
                'user_id_access' => $userId,
            ]);

            return $statement->fetchAll();
        }

        $statement = getDatabase()->prepare(
            self::baseQuery() . '
            INNER JOIN course_students ON course_students.course_id = tasks.course_id
            WHERE course_students.user_id = :user_id
            ORDER BY deadline ASC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public static function forCourse(int $courseId, int $userId, string $role): array
    {
        if ($role === 'admin') {
            $statement = getDatabase()->prepare(self::baseQuery() . '
                WHERE tasks.course_id = :course_id
                ORDER BY tasks.deadline ASC
            ');
            $statement->execute(['course_id' => $courseId]);

            return $statement->fetchAll();
        }

        if ($role === 'teacher') {
            $statement = getDatabase()->prepare(self::baseQuery() . '
                WHERE tasks.course_id = :course_id
                  AND (
                    courses.teacher_id = :user_id_legacy
                    OR EXISTS (
                        SELECT 1
                        FROM course_teachers
                        WHERE course_teachers.course_id = courses.id
                          AND course_teachers.user_id = :user_id_access
                    )
                  )
                ORDER BY tasks.deadline ASC
            ');
            $statement->execute([
                'course_id' => $courseId,
                'user_id_legacy' => $userId,
                'user_id_access' => $userId,
            ]);

            return $statement->fetchAll();
        }

        $statement = getDatabase()->prepare(self::baseQuery() . '
            INNER JOIN course_students ON course_students.course_id = tasks.course_id
            WHERE tasks.course_id = :course_id AND course_students.user_id = :user_id
            ORDER BY tasks.deadline ASC
        ');
        $statement->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
        ]);

        return $statement->fetchAll();
    }

    public static function findForUser(int $taskId, int $userId, string $role): ?array
    {
        if ($role === 'admin') {
            $statement = getDatabase()->prepare(self::baseQuery() . ' WHERE tasks.id = :task_id LIMIT 1');
            $statement->execute(['task_id' => $taskId]);

            return $statement->fetch() ?: null;
        }

        if ($role === 'teacher') {
            $statement = getDatabase()->prepare(
                self::baseQuery() . '
                WHERE tasks.id = :task_id
                  AND (
                    courses.teacher_id = :user_id_legacy
                    OR EXISTS (
                        SELECT 1
                        FROM course_teachers
                        WHERE course_teachers.course_id = courses.id
                          AND course_teachers.user_id = :user_id_access
                    )
                  )
                LIMIT 1'
            );
            $statement->execute([
                'task_id' => $taskId,
                'user_id_legacy' => $userId,
                'user_id_access' => $userId,
            ]);

            return $statement->fetch() ?: null;
        }

        $statement = getDatabase()->prepare(
            self::baseQuery() . '
            INNER JOIN course_students ON course_students.course_id = tasks.course_id
            WHERE tasks.id = :task_id AND course_students.user_id = :user_id
            LIMIT 1'
        );
        $statement->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
        ]);

        return $statement->fetch() ?: null;
    }

    private static function baseQuery(): string
    {
        return '
            SELECT tasks.*, users.full_name AS creator_name, courses.title AS course_title
            FROM tasks
            LEFT JOIN users ON users.id = tasks.created_by
            LEFT JOIN courses ON courses.id = tasks.course_id
        ';
    }

    public static function studentStatus(int $taskId, int $userId): string
    {
        $statement = getDatabase()->prepare('
            SELECT status
            FROM task_submissions
            WHERE task_id = :task_id AND user_id = :user_id
            LIMIT 1
        ');
        $statement->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
        ]);
        $status = $statement->fetchColumn();

        return $status ?: 'not_submitted';
    }

    public static function submissionCount(int $taskId): int
    {
        $statement = getDatabase()->prepare('SELECT COUNT(*) FROM task_submissions WHERE task_id = :task_id');
        $statement->execute(['task_id' => $taskId]);

        return (int) $statement->fetchColumn();
    }

    public static function create(string $title, string $description, string $deadline, int $courseId, int $createdBy): int
    {
        $statement = getDatabase()->prepare('
            INSERT INTO tasks (title, description, deadline, course_id, created_by)
            VALUES (:title, :description, :deadline, :course_id, :created_by)
        ');

        $statement->execute([
            'title' => trim($title),
            'description' => trim($description),
            'deadline' => $deadline,
            'course_id' => $courseId,
            'created_by' => $createdBy,
        ]);

        return (int) getDatabase()->lastInsertId();
    }

    public static function update(int $taskId, string $title, string $description, string $deadline, int $courseId): void
    {
        $statement = getDatabase()->prepare('
            UPDATE tasks
            SET title = :title,
                description = :description,
                deadline = :deadline,
                course_id = :course_id
            WHERE id = :task_id
        ');
        $statement->execute([
            'task_id' => $taskId,
            'title' => trim($title),
            'description' => trim($description),
            'deadline' => $deadline,
            'course_id' => $courseId,
        ]);
    }

    public static function delete(int $taskId): void
    {
        $statement = getDatabase()->prepare('DELETE FROM tasks WHERE id = :task_id');
        $statement->execute(['task_id' => $taskId]);
    }
}
