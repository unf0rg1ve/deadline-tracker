<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class User
{
    public static function findByUsername(string $username): ?array
    {
        $statement = getDatabase()->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => trim($username)]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public static function create(string $username, string $password, string $fullName, string $role = 'student'): int
    {
        $statement = getDatabase()->prepare('
            INSERT INTO users (username, password_hash, full_name, role)
            VALUES (:username, :password_hash, :full_name, :role)
        ');

        $statement->execute([
            'username' => trim($username),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => trim($fullName),
            'role' => $role,
        ]);

        return (int) getDatabase()->lastInsertId();
    }

    public static function authenticate(string $username, string $password): ?array
    {
        $user = self::findByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public static function allByRole(string $role): array
    {
        $statement = getDatabase()->prepare('
            SELECT id, username, full_name, role
            FROM users
            WHERE role = :role
            ORDER BY full_name ASC
        ');
        $statement->execute(['role' => $role]);

        return $statement->fetchAll();
    }
}
