<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/models/Notification.php';
$current_page = basename($_SERVER['SCRIPT_NAME']);
$roleLabels = [
    'student' => t('student'),
    'teacher' => t('teacher'),
    'admin' => t('administrator'),
];
$role = $_SESSION['role'] ?? null;
$displayName = $_SESSION['full_name'] ?? $_SESSION['name'] ?? 'Гость';
preg_match('/./u', $displayName, $initialMatch);
$userInitial = $initialMatch[0] ?? 'U';
$pageTitles = [
    'index.php' => t('page_deadlines'),
    'courses.php' => t('page_courses'),
    'course.php' => t('page_courses'),
    'teacher_page.php' => t('page_my_courses'),
    'groups.php' => t('page_groups'),
    'admin.php' => t('page_admin'),
    'task.php' => t('page_task'),
    'add_task.php' => t('page_add_task'),
    'edit_task.php' => t('page_edit_task'),
    'delete_task.php' => t('page_delete_task'),
    'add_course.php' => t('page_add_course'),
    'edit_course.php' => 'Редактирование курса',
    'add_group.php' => t('page_add_group'),
    'students.php' => 'Студенты',
    'add_student.php' => 'Новый студент',
    'add_material.php' => t('page_add_material'),
    'add_task_attachment.php' => t('page_add_task_attachment'),
    'about.php' => t('page_about'),
    'contact.php' => t('page_contact'),
    'login.php' => t('page_login'),
    'register.php' => t('page_register'),
    'notifications.php' => 'Уведомления',
];
$pageTitle = $pageTitles[$current_page] ?? t('app_name');
$languageQuery = $_GET;
unset($languageQuery['lang']);
$unreadNotifications = isLoggedIn() ? Notification::unreadCount((int) $_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="<?php echo e(currentLanguage()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(t('app_name')); ?></title>
    <script>
        (function () {
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
        })();
    </script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="topbar-inner">
            <a href="index.php" class="brand">
                <span class="brand-mark">DH</span>
                <span>
                    <strong><?php echo e(t('app_name')); ?></strong>
                    <small><?php echo e(t('workspace_subtitle')); ?></small>
                </span>
            </a>

            <nav class="sidebar-nav" aria-label="Основная навигация">
                <a href="index.php" class="nav-link <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">D</span>
                    <span><?php echo e(t('deadlines')); ?></span>
                </a>

                <?php if (isset($_SESSION['role'])): ?>
                    <a href="courses.php" class="nav-link <?php echo ($current_page === 'courses.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">C</span>
                        <span><?php echo e(t('courses')); ?></span>
                    </a>

                    <a href="notifications.php" class="nav-link <?php echo ($current_page === 'notifications.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">N</span>
                        <span>Уведомления</span>
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="nav-badge"><?php echo (int) $unreadNotifications; ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if ($_SESSION['role'] === 'teacher'): ?>
                        <a href="teacher_page.php" class="nav-link <?php echo ($current_page === 'teacher_page.php') ? 'active' : ''; ?>">
                            <span class="nav-icon">T</span>
                            <span><?php echo e(t('my_courses')); ?></span>
                        </a>
                    <?php endif; ?>

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="groups.php" class="nav-link <?php echo ($current_page === 'groups.php') ? 'active' : ''; ?>">
                        <span class="nav-icon">G</span>
                        <span><?php echo e(t('groups')); ?></span>
                    </a>
                    <a href="students.php" class="nav-link <?php echo in_array($current_page, ['students.php', 'add_student.php'], true) ? 'active' : ''; ?>">
                        <span class="nav-icon">S</span>
                        <span>Студенты</span>
                    </a>
                    <a href="admin.php" class="nav-link <?php echo ($current_page === 'admin.php') ? 'active' : ''; ?>">
                            <span class="nav-icon">A</span>
                            <span><?php echo e(t('admin')); ?></span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="about.php" class="nav-link <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">I</span>
                    <span><?php echo e(t('about')); ?></span>
                </a>
                <a href="contact.php" class="nav-link <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>">
                    <span class="nav-icon">@</span>
                    <span><?php echo e(t('contacts')); ?></span>
                </a>
            </nav>

            <div class="user-area">
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="<?php echo e(t('theme')); ?>">
                    <span class="theme-icon" aria-hidden="true">◐</span>
                    <span id="themeToggleLabel" data-light-label="<?php echo e(t('theme_light')); ?>" data-dark-label="<?php echo e(t('theme_dark')); ?>">
                        <?php echo e(t('theme')); ?>
                    </span>
                </button>

                <form method="GET" class="language-switcher" aria-label="<?php echo e(t('language')); ?>">
                    <?php foreach ($languageQuery as $key => $value): ?>
                        <?php if (is_scalar($value)): ?>
                            <input type="hidden" name="<?php echo e((string) $key); ?>" value="<?php echo e((string) $value); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <select name="lang" onchange="this.form.submit()">
                        <?php foreach (languageOptions() as $code => $label): ?>
                            <option value="<?php echo e($code); ?>" <?php echo currentLanguage() === $code ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <?php if (isset($_SESSION['role'])): ?>
                    <div class="user-chip">
                        <span class="avatar"><?php echo e($userInitial); ?></span>
                        <span>
                            <strong><?php echo e($displayName); ?></strong>
                            <small><?php echo e($roleLabels[$role] ?? $role); ?></small>
                        </span>
                    </div>
                    <a href="logout.php" class="button button-ghost"><?php echo e(t('logout')); ?></a>
                <?php else: ?>
                    <a href="login.php" class="button button-primary"><?php echo e(t('login')); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <section class="page-banner">
        <div>
            <p class="eyebrow"><?php echo e(t('workspace_label')); ?></p>
            <h1><?php echo e($pageTitle); ?></h1>
        </div>
    </section>

        <?php $flashMessages = consumeFlashMessages(); ?>
        <?php if (!empty($flashMessages)): ?>
            <section class="flash-stack" aria-live="polite">
                <?php foreach ($flashMessages as $flashMessage): ?>
                    <div class="flash-message flash-<?php echo e($flashMessage['type']); ?>">
                        <strong><?php echo e($flashMessage['type'] === 'error' ? t('flash_error') : t('flash_success')); ?></strong>
                        <span><?php echo e($flashMessage['message']); ?></span>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
