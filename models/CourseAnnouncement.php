<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class CourseAnnouncement
{
    public static function forCourse(int $courseId): array
    {
        $statement = getDatabase()->prepare('
            SELECT course_announcements.*, users.full_name AS author_name, users.role AS author_role
            FROM course_announcements
            LEFT JOIN users ON users.id = course_announcements.author_id
            WHERE course_announcements.course_id = :course_id
            ORDER BY course_announcements.created_at DESC
        ');
        $statement->execute(['course_id' => $courseId]);

        return $statement->fetchAll();
    }

    public static function create(int $courseId, int $authorId, string $title, string $body): int
    {
        $statement = getDatabase()->prepare('
            INSERT INTO course_announcements (course_id, author_id, title, body)
            VALUES (:course_id, :author_id, :title, :body)
        ');
        $statement->execute([
            'course_id' => $courseId,
            'author_id' => $authorId,
            'title' => trim($title),
            'body' => trim($body),
        ]);

        return (int) getDatabase()->lastInsertId();
    }
}
