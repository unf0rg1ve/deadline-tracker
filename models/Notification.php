<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Notification
{
    public static function create(int $userId, string $type, string $title, string $body = '', string $url = ''): int
    {
        $statement = getDatabase()->prepare('
            INSERT INTO notifications (user_id, type, title, body, url)
            VALUES (:user_id, :type, :title, :body, :url)
        ');
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => trim($title),
            'body' => trim($body),
            'url' => trim($url),
        ]);

        return (int) getDatabase()->lastInsertId();
    }

    public static function forUser(int $userId, int $limit = 30): array
    {
        $statement = getDatabase()->prepare('
            SELECT *
            FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ');
        $statement->bindValue('user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public static function unreadCount(int $userId): int
    {
        $statement = getDatabase()->prepare('
            SELECT COUNT(*)
            FROM notifications
            WHERE user_id = :user_id AND read_at IS NULL
        ');
        $statement->execute(['user_id' => $userId]);

        return (int) $statement->fetchColumn();
    }

    public static function markAllRead(int $userId): void
    {
        $statement = getDatabase()->prepare('
            UPDATE notifications
            SET read_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id AND read_at IS NULL
        ');
        $statement->execute(['user_id' => $userId]);
    }

    public static function notifyCourseStudents(int $courseId, string $type, string $title, string $body, string $url, ?int $excludeUserId = null): void
    {
        $statement = getDatabase()->prepare('
            SELECT user_id
            FROM course_students
            WHERE course_id = :course_id
        ');
        $statement->execute(['course_id' => $courseId]);

        foreach ($statement->fetchAll() as $student) {
            $userId = (int) $student['user_id'];
            if ($excludeUserId !== null && $userId === $excludeUserId) {
                continue;
            }

            self::create($userId, $type, $title, $body, $url);
        }
    }

    public static function notifyCourseTeachersAndAdmins(int $courseId, string $type, string $title, string $body, string $url, ?int $excludeUserId = null): void
    {
        $statement = getDatabase()->prepare('
            SELECT DISTINCT users.id
            FROM users
            LEFT JOIN courses ON courses.teacher_id = users.id AND courses.id = :course_id_legacy
            LEFT JOIN course_teachers ON course_teachers.user_id = users.id AND course_teachers.course_id = :course_id_access
            WHERE users.role = "admin"
               OR courses.teacher_id = users.id
               OR course_teachers.user_id = users.id
        ');
        $statement->execute([
            'course_id_legacy' => $courseId,
            'course_id_access' => $courseId,
        ]);

        foreach ($statement->fetchAll() as $recipient) {
            $userId = (int) $recipient['id'];
            if ($excludeUserId !== null && $userId === $excludeUserId) {
                continue;
            }

            self::create($userId, $type, $title, $body, $url);
        }
    }
}
