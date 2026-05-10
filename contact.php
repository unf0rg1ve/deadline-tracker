<?php
// contact.php
include 'header.php';
require_once 'config/database.php';

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $_SESSION['error'] = "Пожалуйста, заполните все поля!";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO messages (name, email, subject, message) 
            VALUES (?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $email, $subject, $message])) {
            $_SESSION['success'] = "Ваше сообщение успешно отправлено! Спасибо.";
        } else {
            $_SESSION['error'] = "Ошибка при отправке сообщения.";
        }
    }

    header("Location: contact.php");
    exit;
}
?>

<main>
    <section class="contact-info">
        <h1>Контакты</h1>
        
        <div class="contact-card">
            <h3>Наша команда</h3>
            <ul>
                <li>
                    <strong>Лукьянов Кирилл</strong><br>
                    <a href="tel:+77479691665">+7 747 969 1665</a>
                </li>
                <li>
                    <strong>Жумагульдинов Рустем</strong><br>
                    <a href="tel:+77774178290">+7 777 417 8290</a>
                </li>
            </ul>
        </div>

        <div class="contact-card">
            <h3>Электронная почта</h3>
            <p>По всем вопросам пишите на почту:</p>
            <p><strong>is4040@ku.edu.kz</strong></p>
            <p><strong>netroot97@gmail.com</strong></p>
        </div>

        <p style="margin-top: 30px; text-align: center; color: #666;">
            Проект выполнен в рамках дисциплин:<br>
            <strong>«Разработка и сопровождение информационных систем»</strong> и 
            <strong>«Проектирование информационных систем»</strong>
        </p>
    </section>

    <!-- Форма обратной связи -->
    <section style="max-width: 700px; margin: 40px auto; padding: 30px; background: #f8f9fa; border-radius: 12px;">
        <h2 style="text-align:center; margin-bottom:25px;">Напишите нам</h2>

        <?php if ($success): ?>
            <div style="background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px; text-align:center;">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px; text-align:center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="name" placeholder="Ваше имя" required style="width:100%; padding:12px; margin:8px 0; border:1px solid #ddd; border-radius:6px;">
            <input type="email" name="email" placeholder="Ваш email" required style="width:100%; padding:12px; margin:8px 0; border:1px solid #ddd; border-radius:6px;">
            <input type="text" name="subject" placeholder="Тема сообщения" required style="width:100%; padding:12px; margin:8px 0; border:1px solid #ddd; border-radius:6px;">
            <textarea name="message" rows="7" placeholder="Ваше сообщение..." required style="width:100%; padding:12px; margin:8px 0; border:1px solid #ddd; border-radius:6px; resize:vertical;"></textarea>
            
            <button type="submit" style="padding:14px 30px; background:#2c3e50; color:white; border:none; border-radius:6px; font-size:1.1em; margin-top:10px;">Отправить сообщение</button>
        </form>
    </section>
</main>

<?php include 'footer.php'; ?>