<?php
require_once 'config/database.php';
require_once 'models/Task.php';
require_once 'config/config.php';

// Проверка ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$taskModel = new Task($pdo);

// Логика сохранения данных (срабатывает при нажатии на "Сохранить")
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $deadline = $_POST['deadline'];

    $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, deadline = ? WHERE id = ?");
    if ($stmt->execute([$title, $desc, $deadline, $id])) {
        $_SESSION['success'] = "Задание успешно обновлено!";
        header("Location: index.php");
        exit;
    }
}

// Получаем данные задания для заполнения формы
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    die("Задание не найдено.");
}

include 'header.php';
?>

<main style="max-width: 600px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2 style="margin-bottom: 20px;">Редактировать задание</h2>

    <form method="POST">
        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Название:</label>
            <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Описание:</label>
            <textarea name="description" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; height: 100px;"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display:block; margin-bottom: 5px; font-weight: bold;">Дедлайн:</label>
            <input type="date" name="deadline" value="<?= $task['deadline'] ?>" required 
                   style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn" style="background:#2c3e50; color:white; padding: 10px 20px; border:none; border-radius:4px; cursor:pointer;">
                Сохранить изменения
            </button>
            <a href="index.php" style="padding: 10px 20px; text-decoration:none; color:#666; border:1px solid #ccc; border-radius:4px;">
                Отмена
            </a>
        </div>
    </form>
</main>

<?php include 'footer.php'; ?>