<?php
class Task {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Получить задания с учётом роли пользователя
    public function getAll($user_id = null, $role = null, $group_name = null) {
    if ($role === 'admin') {
        // Админ видит все задания
        $sql = "
            SELECT t.*, 
                   d.name as discipline_name,
                   u_teacher.name as teacher_name,
                   g.name as group_name
            FROM tasks t
            JOIN disciplines d ON t.discipline_id = d.id
            JOIN users u_teacher ON t.teacher_id = u_teacher.id
            JOIN `groups` g ON t.group_id = g.id
            ORDER BY t.deadline ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
    } 
    elseif ($role === 'teacher') {
        // Преподаватель видит только свои дисциплины
        $sql = "
            SELECT t.*, 
                   d.name as discipline_name,
                   u_teacher.name as teacher_name,
                   g.name as group_name
            FROM tasks t
            JOIN disciplines d ON t.discipline_id = d.id
            JOIN users u_teacher ON t.teacher_id = u_teacher.id
            JOIN `groups` g ON t.group_id = g.id
            JOIN teacher_disciplines td ON td.discipline_id = t.discipline_id
            WHERE td.teacher_id = ?
            ORDER BY t.deadline ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } 
    else {
        // Студент видит задания своей группы
        if (empty($group_name)) {
            return []; // если группа не определена — ничего не показываем
        }
        $sql = "
            SELECT t.*, 
                   d.name as discipline_name,
                   u_teacher.name as teacher_name,
                   g.name as group_name
            FROM tasks t
            JOIN disciplines d ON t.discipline_id = d.id
            JOIN users u_teacher ON t.teacher_id = u_teacher.id
            JOIN `groups` g ON t.group_id = g.id
            WHERE g.name = ?
            ORDER BY t.deadline ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$group_name]);
    }

    return $stmt->fetchAll();
}

    // Создание задания
    public function create($title, $description, $deadline, $discipline_id, $teacher_id, $group_id) {
        $stmt = $this->pdo->prepare("
            INSERT INTO tasks (title, description, deadline, discipline_id, teacher_id, group_id, status)
            VALUES (?, ?, ?, ?, ?, ?, 'в процессе')
        ");
        return $stmt->execute([$title, $description, $deadline, $discipline_id, $teacher_id, $group_id]);
    }

    // Удаление задания
    public function delete($task_id) {
        $stmt = $this->pdo->prepare("DELETE FROM tasks WHERE id = ?");
        return $stmt->execute([$task_id]);
    }

    // Обновление статуса 
    public function updateStatus($task_id, $status) {
        $stmt = $this->pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $task_id]);
    }
}