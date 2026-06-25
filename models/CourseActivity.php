<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class CourseActivity
{
    public static function forCourse(int $courseId, int $limit = 12): array
    {
        $items = array_merge(
            self::announcements($courseId),
            self::materials($courseId),
            self::tasks($courseId),
            self::comments($courseId)
        );

        usort($items, static function (array $a, array $b): int {
            return strcmp($b['created_at'], $a['created_at']);
        });

        return array_slice($items, 0, $limit);
    }

    private static function announcements(int $courseId): array
    {
        $statement = getDatabase()->prepare('
            SELECT course_announcements.id, course_announcements.title, course_announcements.body,
                   course_announcements.created_at, users.full_name AS author_name
            FROM course_announcements
            LEFT JOIN users ON users.id = course_announcements.author_id
            WHERE course_announcements.course_id = :course_id
        ');
        $statement->execute(['course_id' => $courseId]);

        return array_map(static fn(array $row): array => [
            'type' => 'announcement',
            'label' => 'Объявление',
            'title' => $row['title'],
            'body' => $row['body'],
            'author_name' => $row['author_name'],
            'created_at' => $row['created_at'],
            'url' => 'course.php?id=' . $courseId,
        ], $statement->fetchAll());
    }

    private static function materials(int $courseId): array
    {
        $statement = getDatabase()->prepare('
            SELECT course_materials.id, course_materials.title, course_materials.original_name,
                   course_materials.created_at, users.full_name AS author_name
            FROM course_materials
            LEFT JOIN users ON users.id = course_materials.uploaded_by
            WHERE course_materials.course_id = :course_id
        ');
        $statement->execute(['course_id' => $courseId]);

        return array_map(static fn(array $row): array => [
            'type' => 'material',
            'label' => 'Материал',
            'title' => $row['title'],
            'body' => $row['original_name'],
            'author_name' => $row['author_name'],
            'created_at' => $row['created_at'],
            'url' => 'download.php?type=material&id=' . (int) $row['id'],
        ], $statement->fetchAll());
    }

    private static function tasks(int $courseId): array
    {
        $statement = getDatabase()->prepare('
            SELECT tasks.id, tasks.title, tasks.description, tasks.created_at, users.full_name AS author_name
            FROM tasks
            LEFT JOIN users ON users.id = tasks.created_by
            WHERE tasks.course_id = :course_id
        ');
        $statement->execute(['course_id' => $courseId]);

        return array_map(static fn(array $row): array => [
            'type' => 'task',
            'label' => 'Задание',
            'title' => $row['title'],
            'body' => $row['description'],
            'author_name' => $row['author_name'],
            'created_at' => $row['created_at'],
            'url' => 'task.php?id=' . (int) $row['id'],
        ], $statement->fetchAll());
    }

    private static function comments(int $courseId): array
    {
        $statement = getDatabase()->prepare('
            SELECT task_comments.id, task_comments.body, task_comments.created_at,
                   users.full_name AS author_name, tasks.id AS task_id, tasks.title AS task_title
            FROM task_comments
            INNER JOIN tasks ON tasks.id = task_comments.task_id
            LEFT JOIN users ON users.id = task_comments.author_id
            WHERE tasks.course_id = :course_id
        ');
        $statement->execute(['course_id' => $courseId]);

        return array_map(static fn(array $row): array => [
            'type' => 'comment',
            'label' => 'Комментарий',
            'title' => $row['task_title'],
            'body' => $row['body'],
            'author_name' => $row['author_name'],
            'created_at' => $row['created_at'],
            'url' => 'task.php?id=' . (int) $row['task_id'],
        ], $statement->fetchAll());
    }
}
