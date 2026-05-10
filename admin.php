<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// ОБРАБОТКА ФОРМ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Добавление группы
    if ($action === 'add_group' && !empty($_POST['name'])) {
        try {
            $pdo->prepare("INSERT INTO `groups` (name) VALUES (?)")->execute([trim($_POST['name'])]);
            $_SESSION['success'] = "Группа успешно добавлена!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Такая группа уже существует!";
        }
    }

    // Добавление дисциплины
    if ($action === 'add_discipline' && !empty($_POST['name'])) {
        try {
            $pdo->prepare("INSERT INTO disciplines (name) VALUES (?)")->execute([trim($_POST['name'])]);
            $_SESSION['success'] = "Дисциплина успешно добавлена!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Такая дисциплина уже существует!";
        }
    }

    // Удаление группы
    if ($action === 'delete_group' && isset($_POST['id'])) {
        $pdo->prepare("DELETE FROM `groups` WHERE id = ?")->execute([$_POST['id']]);
        $_SESSION['success'] = "Группа удалена!";
    }

    // Удаление дисциплины
    if ($action === 'delete_discipline' && isset($_POST['id'])) {
        $pdo->prepare("DELETE FROM disciplines WHERE id = ?")->execute([$_POST['id']]);
        $_SESSION['success'] = "Дисциплина удалена!";
    }

    if ($action === 'assign_teacher' && !empty($_POST['teacher_id']) && !empty($_POST['discipline_id'])) {
        $pdo->prepare("INSERT IGNORE INTO teacher_disciplines (teacher_id, discipline_id) VALUES (?, ?)")
            ->execute([$_POST['teacher_id'], $_POST['discipline_id']]);
        $_SESSION['success'] = "Дисциплина привязана к преподавателю!";
    }

    header("Location: admin.php");
    exit;
}

// === ЗАГРУЗКА ДАННЫХ ===
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$users = $pdo->query("SELECT * FROM users ORDER BY role DESC, name")->fetchAll();
$groups = $pdo->query("SELECT * FROM `groups` ORDER BY name")->fetchAll();
$disciplines = $pdo->query("SELECT * FROM disciplines ORDER BY name")->fetchAll();
$teachers = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name")->fetchAll();
?>

<?php include 'header.php'; ?>

<main style="max-width: 1300px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #e74c3c; text-align:center;">Панель администратора</h1>

    <?php if ($success): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin:15px 0;"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin:15px 0;"><?= $error ?></div>
    <?php endif; ?>

    <!-- Привязка преподавателя к дисциплине -->
    <h2>Привязать преподавателя к дисциплине:</h2>
    <form method="POST" style="padding:20px; background:#f8f9fa; border-radius:8px; margin-bottom:40px;">
        <input type="hidden" name="action" value="assign_teacher">
        
        <select name="teacher_id" required style="padding:10px; width:320px;">
            <option value="">Выберите преподавателя</option>
            <?php
            $teachers = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name")->fetchAll();
            foreach ($teachers as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="discipline_id" required style="padding:10px; width:380px;">
            <option value="">Выберите дисциплину</option>
            <?php
            $disciplines = $pdo->query("SELECT id, name FROM disciplines ORDER BY name")->fetchAll();
            foreach ($disciplines as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Привязать</button>
    </form>

    <!-- Пользователи -->
    <h2>Все пользователи:</h2>
    <?php
    $users = $pdo->query("SELECT * FROM users ORDER BY role DESC, name")->fetchAll();
    ?>
    <table border="1" cellpadding="10" style="width:100%; border-collapse: collapse;">
        <tr style="background:#f1f1f1;">
            <th>ID</th><th>Имя</th><th>Email</th><th>Роль</th><th>Группа</th><th>Действия</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= strtoupper($u['role']) ?></td>
            <td><?= htmlspecialchars($u['group_name'] ?? '-') ?></td>
            <td>
                <button onclick="if(confirm('Удалить пользователя?')) location.href='delete_user.php?id=<?= $u['id'] ?>'" 
                class="btn btn-danger btn-small">
                Удалить
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- Группы -->
    <h2>Группы:</h2>
    <form method="POST" style="margin-bottom:15px;">
        <input type="hidden" name="action" value="add_group">
        <input type="text" name="name" placeholder="Название группы" required>
        <button type="submit">Добавить группу</button>
    </form>

    <?php
    $groups = $pdo->query("SELECT * FROM `groups` ORDER BY name")->fetchAll();
    foreach ($groups as $g): ?>
        <div style="padding:10px; background:#f8f9fa; margin:5px 0; border-radius:6px; display:flex; justify-content:space-between; align-items:center;">
            <strong><?= htmlspecialchars($g['name']) ?></strong>
            <div>
                <button onclick="editItem(<?= $g['id'] ?>, '<?= htmlspecialchars($g['name']) ?>', 'group', 'Редактирование группы')" 
                        class="btn btn-small">Изменить</button>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="delete_group">
                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                    <button type="submit" onclick="return confirm('Удалить группу?')" class="btn btn-danger btn-small">Удалить</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Дисциплины -->
    <h2 style="margin-top:40px;">Дисциплины:</h2>
    <form method="POST" style="margin-bottom:15px;">
        <input type="hidden" name="action" value="add_discipline">
        <input type="text" name="name" placeholder="Название дисциплины" required>
        <button type="submit">Добавить дисциплину</button>
    </form>

    <?php
    $disciplines = $pdo->query("SELECT * FROM disciplines ORDER BY name")->fetchAll();
    foreach ($disciplines as $d): ?>
    <div style="padding:10px; background:#f8f9fa; margin:5px 0; border-radius:6px; display:flex; justify-content:space-between; align-items:center;">
        <strong><?= htmlspecialchars($d['name']) ?></strong>
        <div>
            <button onclick="editItem(<?= $d['id'] ?>, '<?= htmlspecialchars($d['name']) ?>', 'discipline', 'Редактирование дисциплины')" 
                    class="btn btn-small">Изменить</button>
            
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="delete_discipline">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <button type="submit" onclick="return confirm('Удалить дисциплину?')" class="btn btn-danger btn-small">Удалить</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Сообщения от пользователей -->
    <h2 style="margin-top:50px;">Сообщения от пользователей:</h2>
    <?php
    $messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll();
    ?>
    <?php if (empty($messages)): ?>
        <p>Пока нет сообщений.</p>
    <?php else: ?>
        <table border="1" cellpadding="10" style="width:100%; border-collapse: collapse;">
            <tr style="background:#f1f1f1;">
                <th>Дата</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Тема</th>
                <th>Сообщение</th>
                <th>Статус</th>
            </tr>
            <?php foreach ($messages as $m): ?>
            <tr>
                <td><?= date("d.m.Y H:i", strtotime($m['created_at'])) ?></td>
                <td><?= htmlspecialchars($m['name']) ?></td>
                <td><?= htmlspecialchars($m['email']) ?></td>
                <td><?= htmlspecialchars($m['subject']) ?></td>
                <td style="max-width:400px;"><?= nl2br(htmlspecialchars($m['message'])) ?></td>
                <td><strong><?= $m['status'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</main>

<!-- МОДАЛЬНОЕ ОКНО РЕДАКТИРОВАНИЯ -->
<div id="editModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:1000;">
    <div class="modal-content" style="background:white; margin:8% auto; padding:25px; width:90%; max-width:500px; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,0.3);">
        <h3 id="modalTitle">Редактирование</h3>
        <form method="POST" action="edit_handler.php">
            <input type="hidden" name="edit_id" id="edit_id">
            <input type="hidden" name="edit_type" id="edit_type">
            
            <label>Новое название:</label><br><br>
            <input type="text" name="new_name" id="new_name" style="width:100%; padding:12px; font-size:1.1em; border:1px solid #ddd; border-radius:6px;" required>
            <br><br>
            <button type="submit" class="btn">Сохранить изменения</button>
            <button type="button" onclick="closeModal()" class="btn btn-danger">Отмена</button>
        </form>
    </div>
</div>

<script>
function editItem(id, name, type, title) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_type').value = type;
    document.getElementById('new_name').value = name;
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include 'footer.php'; ?>