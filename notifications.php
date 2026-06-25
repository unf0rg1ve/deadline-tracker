<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Notification.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (isset($_POST['mark_all_read'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        Notification::markAllRead((int) $_SESSION['user_id']);
        flash('success', 'Уведомления отмечены прочитанными.');
    } else {
        flash('error', t('csrf_error'));
    }

    header('Location: notifications.php');
    exit;
}

$notifications = Notification::forUser((int) $_SESSION['user_id']);
$unreadCount = Notification::unreadCount((int) $_SESSION['user_id']);

include 'header.php';
?>

<main>
    <div class="section-header">
        <h1>Уведомления</h1>
        <?php if ($unreadCount > 0): ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <button type="submit" name="mark_all_read">Отметить всё прочитанным</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <section class="card">
            <p>Уведомлений пока нет.</p>
        </section>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <?php preg_match('/./u', $notification['type'], $notificationInitial); ?>
                <a class="notification-item <?php echo empty($notification['read_at']) ? 'unread' : ''; ?>" href="<?php echo e($notification['url'] ?: '#'); ?>">
                    <span class="notification-type"><?php echo e(strtoupper($notificationInitial[0] ?? 'N')); ?></span>
                    <span>
                        <strong><?php echo e($notification['title']); ?></strong>
                        <?php if (!empty($notification['body'])): ?>
                            <span><?php echo e($notification['body']); ?></span>
                        <?php endif; ?>
                        <small><?php echo e($notification['created_at']); ?></small>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
