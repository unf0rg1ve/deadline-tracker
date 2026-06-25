<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Course.php';

class StudentGroup
{
    public static function all(): array
    {
        $statement = getDatabase()->query('
            SELECT student_groups.*, COUNT(DISTINCT group_students.user_id) AS students_count
            FROM student_groups
            LEFT JOIN group_students ON group_students.group_id = student_groups.id
            GROUP BY student_groups.id
            ORDER BY student_groups.name ASC
        ');

        return $statement->fetchAll();
    }

    public static function find(int $groupId): ?array
    {
        $statement = getDatabase()->prepare('SELECT * FROM student_groups WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $groupId]);

        return $statement->fetch() ?: null;
    }

    public static function create(string $name, string $description): int
    {
        $statement = getDatabase()->prepare('
            INSERT INTO student_groups (name, description)
            VALUES (:name, :description)
        ');
        $statement->execute([
            'name' => trim($name),
            'description' => trim($description),
        ]);

        return (int) getDatabase()->lastInsertId();
    }

    public static function firstId(): ?int
    {
        $id = getDatabase()->query('SELECT id FROM student_groups ORDER BY id ASC LIMIT 1')->fetchColumn();

        return $id ? (int) $id : null;
    }

    public static function students(int $groupId): array
    {
        $statement = getDatabase()->prepare('
            SELECT users.id, users.username, users.full_name
            FROM group_students
            INNER JOIN users ON users.id = group_students.user_id
            WHERE group_students.group_id = :group_id
            ORDER BY users.full_name ASC
        ');
        $statement->execute(['group_id' => $groupId]);

        return $statement->fetchAll();
    }

    public static function allStudentsWithGroups(): array
    {
        $statement = getDatabase()->query('
            SELECT users.id, users.username, users.full_name,
                student_groups.id AS group_id,
                student_groups.name AS group_name
            FROM users
            LEFT JOIN group_students ON group_students.user_id = users.id
            LEFT JOIN student_groups ON student_groups.id = group_students.group_id
            WHERE users.role = "student"
            ORDER BY student_groups.name ASC, users.full_name ASC
        ');

        return $statement->fetchAll();
    }

    public static function addStudent(int $groupId, int $userId): void
    {
        self::setStudentGroup($groupId, $userId);
    }

    public static function setStudentGroup(int $groupId, int $userId): void
    {
        getDatabase()->prepare('DELETE FROM group_students WHERE user_id = :user_id')
            ->execute(['user_id' => $userId]);
        getDatabase()->prepare('DELETE FROM course_students WHERE user_id = :user_id')
            ->execute(['user_id' => $userId]);

        $statement = getDatabase()->prepare('
            INSERT OR IGNORE INTO group_students (group_id, user_id)
            VALUES (:group_id, :user_id)
        ');
        $statement->execute([
            'group_id' => $groupId,
            'user_id' => $userId,
        ]);

        Course::enrollStudentInGroupCourses($groupId, $userId);
    }
}
