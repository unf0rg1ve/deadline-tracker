<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class CourseMaterial
{
    public static function forCourse(int $courseId): array
    {
        $statement = getDatabase()->prepare('
            SELECT course_materials.*, users.full_name AS uploader_name
            FROM course_materials
            LEFT JOIN users ON users.id = course_materials.uploaded_by
            WHERE course_materials.course_id = :course_id
            ORDER BY course_materials.created_at DESC
        ');
        $statement->execute(['course_id' => $courseId]);

        return $statement->fetchAll();
    }

    public static function create(int $courseId, int $uploadedBy, string $title, array $fileData): int
    {
        $statement = getDatabase()->prepare('
            INSERT INTO course_materials (
                course_id, uploaded_by, title, original_name, stored_name, mime_type, file_size
            )
            VALUES (
                :course_id, :uploaded_by, :title, :original_name, :stored_name, :mime_type, :file_size
            )
        ');
        $statement->execute([
            'course_id' => $courseId,
            'uploaded_by' => $uploadedBy,
            'title' => trim($title),
            'original_name' => $fileData['original_name'],
            'stored_name' => $fileData['stored_name'],
            'mime_type' => $fileData['mime_type'],
            'file_size' => $fileData['file_size'],
        ]);

        return (int) getDatabase()->lastInsertId();
    }

    public static function find(int $materialId): ?array
    {
        $statement = getDatabase()->prepare('SELECT * FROM course_materials WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $materialId]);

        return $statement->fetch() ?: null;
    }
}
