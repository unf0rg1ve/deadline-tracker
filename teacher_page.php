<?php
include 'header.php';
require_once 'config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}
?>

<main>
    <h1>Мои дисциплины и задания</h1>
    <p><strong>Преподаватель:</strong> <?php echo htmlspecialchars($_SESSION['name']); ?></p>

    <h2>Мои дисциплины</h2>
    <div style="margin-bottom:30px;">
        <?php
        $stmt = $pdo->prepare("
            SELECT d.name 
            FROM teacher_disciplines td
            JOIN disciplines d ON td.discipline_id = d.id
            WHERE td.teacher_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $myDisciplines = $stmt->fetchAll();
        ?>
        <?php foreach ($myDisciplines as $i => $d): ?>
            <span class="discipline-tag discipline-<?= ($i % 6) + 1 ?>">
                <?= htmlspecialchars($d['name']) ?>
            </span>
        <?php endforeach; ?>
    </div>

    <h2>Задания по моим дисциплинам</h2>
    <?php
    $stmt = $pdo->prepare("
        SELECT t.*, d.name as discipline_name, g.name as group_name
        FROM tasks t
        JOIN disciplines d ON t.discipline_id = d.id
        JOIN `groups` g ON t.group_id = g.id
        WHERE t.teacher_id = ?
        ORDER BY t.deadline ASC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $tasks = $stmt->fetchAll();
    ?>

    <?php if (empty($tasks)): ?>
        <p>Пока нет заданий.</p>
    <?php else: ?>
        <div class="cards-container">
            <?php foreach ($tasks as $task): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($task['title']) ?></h3>
                    <p><?= htmlspecialchars($task['description'] ?? '—') ?></p>
                    <div class="task-meta">
                        <span class="discipline-tag">📘 <?= htmlspecialchars($task['discipline_name']) ?></span>
                        <small><strong>Группа:</strong> <?= htmlspecialchars($task['group_name']) ?></small>
                        <small><strong>Дедлайн:</strong> <?= date("d.m.Y", strtotime($task['deadline'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="text-align:center; margin-top:40px;">
        <a href="index.php" class="btn">← На главную</a>
    </div>
</main>

<?php include 'footer.php'; ?>