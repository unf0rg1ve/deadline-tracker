<?php
require_once 'config/database.php';

header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.id, 
           t.title, 
           t.description, 
           DATE_FORMAT(t.deadline, '%d.%m.%Y') as deadline_formatted,
           t.status, 
           d.name as discipline
    FROM tasks t
    JOIN disciplines d ON t.discipline_id = d.id
    WHERE t.title LIKE ? 
       OR t.description LIKE ? 
       OR d.name LIKE ?
    LIMIT 15
");

$like = "%$q%";
$stmt->execute([$like, $like, $like]);

echo json_encode($stmt->fetchAll());