<?php
require_once 'config/database.php';
require_once 'models/Task.php';
require_once 'config/config.php';
require_once 'gemini_api.php';

$gemini = new GeminiAPI(GEMINI_API_KEY);
session_start();

if (!isset($_SESSION['user_id'])) {
   header("Location: login.php");
   exit;
}

$taskModel = new Task($pdo);
$tasks = $taskModel->getAll(
    $_SESSION['user_id'], 
    $_SESSION['role'], 
    $_SESSION['group_name'] ?? null
);

$today = date('Y-m-d');
$current_time = strtotime($today);

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error']   ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Deadline Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>  

<main>
    <h2>Ваши текущие задания — <?php echo htmlspecialchars($_SESSION['name']); ?></h2>

    <!-- Живой поиск -->
    <div style="margin: 20px 0 25px;">
        <input type="text" 
               id="searchInput" 
               placeholder="Поиск по названию, описанию или дисциплине..." 
               style="width: 100%; max-width: 700px; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 1.05em;">
        <div id="searchResults" style="margin-top: 10px;"></div>
    </div>

    <?php if ($success): ?>
        <div style="background:#d4edda; color:#155724; padding:12px; border-radius:5px; margin-bottom:20px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:5px; margin-bottom:20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($tasks)): ?>
        <p style="text-align:center; padding:40px; color:#666; font-size:1.1em;">
            У вас пока нет заданий.<br>Добавьте первое задание ниже.
        </p>
    <?php else: ?>
        <div class="cards-container" id="tasksContainer">
            <?php foreach ($tasks as $task): 
                $deadline_time = strtotime($task['deadline']);
                $diff = ($deadline_time - $current_time) / (60 * 60 * 24);

                if ($diff < 0) $status_class = 'status-critical';
                elseif ($diff <= 3) $status_class = 'status-warning';
                else $status_class = 'status-normal';
                $label = ($diff < 0) ? 'Просрочено' : (($diff <= 3) ? 'Срочно' : 'Время есть');
            ?>
                <div class="card <?php echo $status_class; ?>">
                    <span class="status-label"><?php echo $label; ?></span>

                    <h3><?php echo htmlspecialchars($task['title']); ?></h3>
                    <p><?php echo htmlspecialchars($task['description'] ?? '—'); ?></p>

                    <div class="task-meta">
                    <small><strong>Дедлайн:</strong> <?php echo date("d.m.Y", $deadline_time); ?></small>
                            
                    <?php 
                        $hash = crc32($task['discipline_name'] ?? 'default');
                        $colorNum = ($hash % 6) + 1;
                    ?>
                    <span class="discipline-tag discipline-<?= $colorNum ?>">
                        <?php echo htmlspecialchars($task['discipline_name'] ?? '—'); ?>
                    </span>
                            
                    <small><strong>Преподаватель:</strong> <?php echo htmlspecialchars($task['teacher_name']); ?></small>
                    <small><strong>Группа:</strong> <?php echo htmlspecialchars($task['group_name']); ?></small>
                    <small><strong>Статус:</strong> <?php echo htmlspecialchars($task['status']); ?></small>
                            
                    <!-- Файлы -->
                    <?php if (!empty($task['file_path'])): ?>
                        <small><strong>📎 Файл:</strong> 
                            <a href="<?= htmlspecialchars($task['file_path']) ?>" target="_blank">Скачать</a>
                        </small>
                    <?php endif; ?>
                    
                    <?php if (!empty($task['student_file_path'])): ?>
                        <small><strong>📎 Отчёт:</strong> 
                            <a href="<?= htmlspecialchars($task['student_file_path']) ?>" target="_blank" style="color:#27ae60;">Скачать</a>
                        </small>
                    <?php endif; ?>
                </div>

                    <div class="task-actions">
                        <?php if ($_SESSION['role'] !== 'student'): ?>
                            <!-- Для преподавателя и админа -->
                            <form method="POST" action="update_status.php" class="status-form">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <select name="status" class="status-select">
                                    <option value="в процессе" <?= $task['status']=='в процессе'?'selected':'' ?>>В процессе</option>
                                    <option value="сдана" <?= $task['status']=='сдана'?'selected':'' ?>>Сдана</option>
                                    <option value="проверена" <?= $task['status']=='проверена'?'selected':'' ?>>Проверена</option>
                                </select>
                                <button type="submit" class="btn btn-small">Изменить</button>
                            </form>
                        
                            <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn btn-small btn-edit">Редактировать</a>
                        <?php else: ?>
                            <!-- Для студента — компактная форма сдачи с файлом -->
                            <form method="POST" action="update_status.php" enctype="multipart/form-data" class="status-form" style="display:flex; flex-direction:column; gap:8px;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                
                                <select name="status" class="status-select">
                                    <option value="в процессе">В процессе</option>
                                    <option value="сдана">Сдать задание</option>
                                </select>
                                                
                                <label style="font-size:0.85em; color:#555; margin-top:4px;">Прикрепить отчёт:</label>
                                <input type="file" name="student_file" style="font-size:0.9em; padding:4px; border:1px solid #ddd; border-radius:4px; width:100%;">
                                                
                                <button type="submit" class="btn btn-small" style="margin-top:6px;">Сдать задание</button>
                            </form>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-ai" 
                            data-task-id="<?= $task['id'] ?>"
                            data-title="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>"
                            data-description="<?= htmlspecialchars($task['description'] ?? '', ENT_QUOTES) ?>"
                            data-deadline="<?= $task['deadline'] ?>">
                            🤖 Проанализировать ИИ
                        </button>
                        
                        <?php if ($_SESSION['role'] !== 'student'): ?>
                            <button type="button" class="btn btn-danger btn-small delete-btn" data-task-id="<?= $task['id'] ?>">
                                Удалить
                            </button>
                        <?php endif; ?>
                    </div>
                        
                    <div id="ai-result-<?= $task['id'] ?>" class="ai-result"></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

   <!-- Форма добавления задания -->
<?php if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
<div style="margin-top:40px; padding:25px; border:2px dashed #3498db; border-radius:8px;">
    <h3>+ Добавить новое задание</h3>
    <form method="POST" action="add_task.php">
        <input type="text" name="title" placeholder="Название задания" required style="width:100%; padding:10px; margin:8px 0;">
        <textarea name="description" placeholder="Описание задания" style="width:100%; padding:10px; margin:8px 0; height:80px;"></textarea>
        
        <!-- Дисциплина -->
        <select name="discipline_id" required style="width:100%; padding:10px; margin:8px 0;">
            <option value="">Выберите дисциплину</option>
            <?php
            $disciplines = $pdo->query("SELECT id, name FROM disciplines ORDER BY name")->fetchAll();
            foreach ($disciplines as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        
        <!-- Группа -->
        <select name="group_id" required style="width:100%; padding:10px; margin:8px 0;">
            <option value="">Выберите группу</option>
            <?php
            $groups = $pdo->query("SELECT id, name FROM `groups` ORDER BY name")->fetchAll();
            if (empty($groups)) {
                echo '<option value="">Нет групп в базе</option>';
            } else {
                foreach ($groups as $g): ?>
                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                <?php endforeach;
            }
            ?>
        </select>
        
        <input type="date" name="deadline" required style="width:100%; padding:10px; margin:8px 0;">
        <button type="submit" style="padding:12px 25px; background:#2c3e50; color:white; margin-top:10px;">Добавить задание</button>
    </form>
</div>
<?php endif; ?>
</main>
<script>
// Асинхронное удаление задания
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-btn')) {
        const btn = e.target;
        const taskId = btn.dataset.taskId;
        const card = btn.closest('.card');

        if (confirm('Удалить это задание?')) {
            fetch('delete_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'task_id=' + taskId
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    card.style.transition = 'opacity 0.5s, transform 0.4s';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 500);
                } else {
                    alert('Не удалось удалить задание');
                }
            })
            .catch(() => alert('Ошибка соединения'));
        }
    }
});

    // Вывод анализа ИИ
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-ai')) {
        const btn = e.target;
        const taskId = btn.dataset.taskId;
        const title = btn.dataset.title;
        const description = btn.dataset.description || '';
        const deadline = btn.dataset.deadline;

        const resultDiv = document.getElementById('ai-result-' + taskId);
        resultDiv.style.display = 'block';
        resultDiv.innerHTML = '<p><strong>ИИ думает...</strong></p>';

        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('title', title);
        formData.append('description', description);
        formData.append('deadline', deadline);

        fetch('ai_analyze.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            resultDiv.innerHTML = `<strong>Анализ ИИ:</strong><pre style="white-space: pre-wrap; background:#f8f9fa; padding:12px; border-radius:6px;">${text}</pre>`;
        })
        .catch(err => {
            resultDiv.innerHTML = '<p style="color:red;">Ошибка при обращении к ИИ</p>';
            console.error(err);
        });
    }
});
// Поиск AJAX 
document.getElementById('searchInput').addEventListener('input', function() {
    const query = this.value.trim();
    const resultsDiv = document.getElementById('searchResults');
    const tasksContainer = document.getElementById('tasksContainer');

    if (query.length < 2) {
        resultsDiv.innerHTML = '';
        tasksContainer.style.display = 'flex';
        return;
    }

    fetch('search_tasks.php?q=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                resultsDiv.innerHTML = '<p style="color:#666; padding:10px;">Ничего не найдено по запросу "' + query + '"</p>';
                return;
            }

            let html = '<h3>Результаты поиска (' + data.length + '):</h3>';
            data.forEach(task => {
                html += `
                    <div style="padding:12px; margin:6px 0; background:#f8f9fa; border-radius:6px;">
                        <strong>${task.title}</strong><br>
                        ${task.discipline ? '<small>Дисциплина: ' + task.discipline + '</small><br>' : ''}
                        <small>Дедлайн: ${task.deadline_formatted}</small>
                    </div>`;
            });
            resultsDiv.innerHTML = html;
            tasksContainer.style.display = 'none';
        })
        .catch(() => {
            resultsDiv.innerHTML = '<p style="color:red;">Ошибка поиска</p>';
        });
});
</script>
<?php include 'footer.php'; ?>
