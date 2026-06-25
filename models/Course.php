<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Course
{
    public static function forUser(int $userId, string $role): array
    {
        if ($role === 'admin') {
            return self::all();
        }

        if ($role === 'teacher') {
            $statement = getDatabase()->prepare(self::baseQuery() . '
                WHERE EXISTS (
                    SELECT 1
                    FROM course_teachers
                    WHERE course_teachers.course_id = courses.id
                      AND course_teachers.user_id = :user_id_access
                )
                OR courses.teacher_id = :user_id_legacy
                ORDER BY courses.title ASC
            ');
            $statement->execute([
                'user_id_access' => $userId,
                'user_id_legacy' => $userId,
            ]);

            return $statement->fetchAll();
        }

        $statement = getDatabase()->prepare(self::baseQuery() . '
            INNER JOIN course_students ON course_students.course_id = courses.id
            WHERE course_students.user_id = :user_id
            ORDER BY courses.title ASC
        ');
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public static function findForUser(int $courseId, int $userId, string $role): ?array
    {
        if ($role === 'admin') {
            $statement = getDatabase()->prepare(self::baseQuery() . ' WHERE courses.id = :course_id LIMIT 1');
            $statement->execute(['course_id' => $courseId]);

            return $statement->fetch() ?: null;
        }

        if ($role === 'teacher') {
            $statement = getDatabase()->prepare(self::baseQuery() . '
                WHERE courses.id = :course_id
                  AND (
                    courses.teacher_id = :user_id_legacy
                    OR EXISTS (
                        SELECT 1
                        FROM course_teachers
                        WHERE course_teachers.course_id = courses.id
                          AND course_teachers.user_id = :user_id_access
                    )
                  )
                LIMIT 1
            ');
            $statement->execute([
                'course_id' => $courseId,
                'user_id_legacy' => $userId,
                'user_id_access' => $userId,
            ]);

            return $statement->fetch() ?: null;
        }

        $statement = getDatabase()->prepare(self::baseQuery() . '
            INNER JOIN course_students ON course_students.course_id = courses.id
            WHERE courses.id = :course_id AND course_students.user_id = :user_id
            LIMIT 1
        ');
        $statement->execute([
            'course_id' => $courseId,
            'user_id' => $userId,
        ]);

        return $statement->fetch() ?: null;
    }

    public static function all(): array
    {
        $statement = getDatabase()->query(self::baseQuery() . '
            ORDER BY courses.title ASC
        ');

        return $statement->fetchAll();
    }

    private static function baseQuery(): string
    {
        return '
            SELECT courses.*,
                (
                    SELECT GROUP_CONCAT(student_groups.name, ", ")
                    FROM course_groups
                    INNER JOIN student_groups ON student_groups.id = course_groups.group_id
                    WHERE course_groups.course_id = courses.id
                ) AS group_name,
                (
                    SELECT GROUP_CONCAT(users.full_name, ", ")
                    FROM course_teachers
                    INNER JOIN users ON users.id = course_teachers.user_id
                    WHERE course_teachers.course_id = courses.id
                ) AS teacher_name
            FROM courses
        ';
    }

    public static function create(string $title, string $description, array $teacherIds, array $groupIds): int
    {
        $teacherIds = self::normalizeIds($teacherIds);
        $groupIds = self::normalizeIds($groupIds);
        $primaryTeacherId = $teacherIds[0] ?? null;
        $primaryGroupId = $groupIds[0] ?? null;

        $statement = getDatabase()->prepare('
            INSERT INTO courses (title, description, teacher_id, group_id)
            VALUES (:title, :description, :teacher_id, :group_id)
        ');
        $statement->execute([
            'title' => trim($title),
            'description' => trim($description),
            'teacher_id' => $primaryTeacherId,
            'group_id' => $primaryGroupId,
        ]);

        $courseId = (int) getDatabase()->lastInsertId();
        self::syncTeachers($courseId, $teacherIds);
        self::syncGroups($courseId, $groupIds);

        return $courseId;
    }

    public static function groupIds(int $courseId): array
    {
        $statement = getDatabase()->prepare('SELECT group_id FROM course_groups WHERE course_id = :course_id');
        $statement->execute(['course_id' => $courseId]);

        return array_map('intval', array_column($statement->fetchAll(), 'group_id'));
    }

    public static function teacherIds(int $courseId): array
    {
        $statement = getDatabase()->prepare('SELECT user_id FROM course_teachers WHERE course_id = :course_id');
        $statement->execute(['course_id' => $courseId]);

        return array_map('intval', array_column($statement->fetchAll(), 'user_id'));
    }

    public static function update(int $courseId, string $title, string $description, array $teacherIds, array $groupIds): void
    {
        $teacherIds = self::normalizeIds($teacherIds);
        $groupIds = self::normalizeIds($groupIds);
        $primaryTeacherId = $teacherIds[0] ?? null;
        $primaryGroupId = $groupIds[0] ?? null;

        $statement = getDatabase()->prepare('
            UPDATE courses
            SET title = :title,
                description = :description,
                teacher_id = :teacher_id,
                group_id = :group_id
            WHERE id = :course_id
        ');
        $statement->execute([
            'course_id' => $courseId,
            'title' => trim($title),
            'description' => trim($description),
            'teacher_id' => $primaryTeacherId,
            'group_id' => $primaryGroupId,
        ]);

        getDatabase()->prepare('DELETE FROM course_teachers WHERE course_id = :course_id')
            ->execute(['course_id' => $courseId]);
        getDatabase()->prepare('DELETE FROM course_groups WHERE course_id = :course_id')
            ->execute(['course_id' => $courseId]);
        getDatabase()->prepare('DELETE FROM course_students WHERE course_id = :course_id')
            ->execute(['course_id' => $courseId]);

        self::syncTeachers($courseId, $teacherIds);
        self::syncGroups($courseId, $groupIds);
    }

    public static function syncTeachers(int $courseId, array $teacherIds): void
    {
        $teacherIds = self::normalizeIds($teacherIds);
        if (empty($teacherIds)) {
            return;
        }

        $statement = getDatabase()->prepare('
            INSERT OR IGNORE INTO course_teachers (course_id, user_id, role)
            VALUES (:course_id, :user_id, "teacher")
        ');

        foreach ($teacherIds as $teacherId) {
            $statement->execute([
                'course_id' => $courseId,
                'user_id' => $teacherId,
            ]);
        }
    }

    public static function syncGroups(int $courseId, array $groupIds): void
    {
        $groupIds = self::normalizeIds($groupIds);
        if (empty($groupIds)) {
            return;
        }

        $statement = getDatabase()->prepare('
            INSERT OR IGNORE INTO course_groups (course_id, group_id)
            VALUES (:course_id, :group_id)
        ');

        foreach ($groupIds as $groupId) {
            $statement->execute([
                'course_id' => $courseId,
                'group_id' => $groupId,
            ]);
            self::enrollGroupStudents($courseId, $groupId);
        }
    }

    public static function enrollStudentInGroupCourses(int $groupId, int $userId): void
    {
        $statement = getDatabase()->prepare('
            INSERT OR IGNORE INTO course_students (course_id, user_id)
            SELECT course_id, :user_id
            FROM course_groups
            WHERE group_id = :group_id
        ');
        $statement->execute([
            'group_id' => $groupId,
            'user_id' => $userId,
        ]);
    }

    private static function enrollGroupStudents(int $courseId, int $groupId): void
    {
        $statement = getDatabase()->prepare('
            INSERT OR IGNORE INTO course_students (course_id, user_id)
            SELECT :course_id, user_id
            FROM group_students
            WHERE group_id = :group_id
        ');
        $statement->execute([
            'course_id' => $courseId,
            'group_id' => $groupId,
        ]);
    }

    private static function normalizeIds(array $ids): array
    {
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, static fn(int $id): bool => $id > 0);

        return array_values(array_unique($ids));
    }
}
