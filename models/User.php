<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("
            SELECT id, name, email, password, role, group_name 
            FROM users WHERE email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function create($name, $email, $password, $role = 'student', $group_name = null) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, email, password, role, group_name)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$name, $email, $hashed, $role, $group_name]);
    }
}