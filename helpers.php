<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function currentLanguage(): string
{
    return $_SESSION['lang'] ?? 'ru';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consumeFlashMessages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

function uploadBaseDir(): string
{
    $dir = __DIR__ . '/storage/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir;
}

function validateUploadedFile(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return 'Файл не был загружен.';
    }

    $maxSize = 10 * 1024 * 1024;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxSize) {
        return 'Размер файла должен быть от 1 байта до 10 МБ.';
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'png', 'jpg', 'jpeg', 'zip'];
    if (!in_array($extension, $allowedExtensions, true)) {
        return 'Недопустимый тип файла.';
    }

    return null;
}

function storeUploadedFile(array $file, string $bucket): array
{
    $bucketDir = uploadBaseDir() . '/' . $bucket;
    if (!is_dir($bucketDir)) {
        mkdir($bucketDir, 0775, true);
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $bucketDir . '/' . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }

    return [
        'original_name' => $file['name'],
        'stored_name' => $bucket . '/' . $storedName,
        'mime_type' => $file['type'] ?: 'application/octet-stream',
        'file_size' => (int) $file['size'],
    ];
}

function formatFileSize(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return round($bytes / 1024 / 1024, 1) . ' МБ';
    }

    return max(1, round($bytes / 1024, 1)) . ' КБ';
}

function excerpt(string $value, int $length = 140): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value));

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($value) > $length ? mb_substr($value, 0, $length) . '...' : $value;
    }

    return strlen($value) > $length ? substr($value, 0, $length) . '...' : $value;
}

function languageOptions(): array
{
    return [
        'ru' => 'Русский',
        'kk' => 'Қазақша',
        'en' => 'English',
    ];
}

function t(string $key): string
{
    $translations = [
        'ru' => [
            'app_name' => 'Deadline Hub',
            'workspace_label' => 'Workspace',
            'workspace_subtitle' => 'Учебное пространство',
            'dashboard' => 'Рабочая панель',
            'deadlines' => 'Дедлайны',
            'courses' => 'Курсы',
            'my_courses' => 'Мои курсы',
            'groups' => 'Группы',
            'admin' => 'Админ',
            'about' => 'О проекте',
            'contacts' => 'Контакты',
            'login' => 'Войти',
            'logout' => 'Выйти',
            'theme' => 'Тема',
            'theme_light' => 'Светлая',
            'theme_dark' => 'Тёмная',
            'language' => 'Язык',
            'student' => 'Студент',
            'teacher' => 'Преподаватель',
            'administrator' => 'Администратор',
            'page_deadlines' => 'Дедлайны',
            'page_courses' => 'Курсы',
            'page_my_courses' => 'Мои курсы',
            'page_groups' => 'Учебные группы',
            'page_admin' => 'Администрирование',
            'page_task' => 'Задание',
            'page_add_task' => 'Новое задание',
            'page_edit_task' => 'Редактирование задания',
            'page_delete_task' => 'Удаление задания',
            'page_add_course' => 'Новый курс',
            'page_add_group' => 'Новая группа',
            'page_add_material' => 'Новый материал',
            'page_add_task_attachment' => 'Новый файл задания',
            'page_about' => 'О проекте',
            'page_contact' => 'Контакты',
            'page_login' => 'Вход',
            'page_register' => 'Регистрация',
            'status_not_submitted' => 'Не сдано',
            'status_submitted' => 'На проверке',
            'status_reviewed' => 'Проверено',
            'status_needs_revision' => 'Нужна доработка',
            'hero_badge' => 'Учебная платформа',
            'hero_title' => 'Дедлайны, курсы и сдачи работ в одном пространстве',
            'hero_text' => 'Войдите, чтобы увидеть свои задания, курсы и статусы проверки.',
            'current_tasks' => 'Ваши текущие задания',
            'add_task' => '+ Добавить новую задачу',
            'csrf_error' => 'Сессия формы устарела. Повторите действие.',
            'flash_success' => 'Готово',
            'flash_error' => 'Ошибка',
            'login_success' => 'Вы вошли в систему.',
            'register_success' => 'Аккаунт создан.',
            'task_created' => 'Задание создано.',
            'task_updated' => 'Задание обновлено.',
            'task_deleted' => 'Задание удалено.',
            'work_submitted' => 'Работа отправлена.',
            'review_saved' => 'Проверка сохранена.',
            'course_created' => 'Курс создан.',
            'group_created' => 'Группа создана.',
            'deadline_overview_text' => 'Фильтруйте задания по курсу, срочности и статусу, чтобы быстро понять приоритеты.',
            'deadline_metrics' => 'Сводка по дедлайнам',
            'metric_total' => 'Всего заданий',
            'metric_overdue' => 'Просрочено',
            'metric_urgent' => 'Срочно',
            'metric_submitted' => 'Сдано',
            'metric_planned' => 'Есть время',
            'filter_search' => 'Поиск',
            'filter_search_placeholder' => 'Название, описание или курс',
            'filter_course' => 'Курс',
            'filter_all_courses' => 'Все курсы',
            'filter_urgency' => 'Срочность',
            'filter_all_deadlines' => 'Все дедлайны',
            'filter_progress' => 'Статус сдачи',
            'filter_all_statuses' => 'Все статусы',
            'apply_filters' => 'Показать',
            'reset_filters' => 'Сбросить',
            'week_deadlines' => 'Ближайшие 7 дней',
            'deadline_overdue' => 'Просрочено',
            'deadline_urgent' => 'Срочно',
            'deadline_normal' => 'Время есть',
            'task_progress' => 'Статус',
            'submission_count' => 'Сдач',
            'empty_tasks_title' => 'Заданий не найдено',
            'empty_tasks_text' => 'Измените фильтры или создайте новое задание для курса.',
        ],
        'kk' => [
            'app_name' => 'Deadline Hub',
            'workspace_label' => 'Workspace',
            'workspace_subtitle' => 'Оқу кеңістігі',
            'dashboard' => 'Жұмыс панелі',
            'deadlines' => 'Мерзімдер',
            'courses' => 'Курстар',
            'my_courses' => 'Менің курстарым',
            'groups' => 'Топтар',
            'admin' => 'Әкімші',
            'about' => 'Жоба туралы',
            'contacts' => 'Байланыс',
            'login' => 'Кіру',
            'logout' => 'Шығу',
            'theme' => 'Тақырып',
            'theme_light' => 'Жарық',
            'theme_dark' => 'Қараңғы',
            'language' => 'Тіл',
            'student' => 'Студент',
            'teacher' => 'Оқытушы',
            'administrator' => 'Әкімші',
            'page_deadlines' => 'Мерзімдер',
            'page_courses' => 'Курстар',
            'page_my_courses' => 'Менің курстарым',
            'page_groups' => 'Оқу топтары',
            'page_admin' => 'Әкімші панелі',
            'page_task' => 'Тапсырма',
            'page_add_task' => 'Жаңа тапсырма',
            'page_edit_task' => 'Тапсырманы өңдеу',
            'page_delete_task' => 'Тапсырманы жою',
            'page_add_course' => 'Жаңа курс',
            'page_add_group' => 'Жаңа топ',
            'page_add_material' => 'Жаңа материал',
            'page_add_task_attachment' => 'Тапсырманың жаңа файлы',
            'page_about' => 'Жоба туралы',
            'page_contact' => 'Байланыс',
            'page_login' => 'Кіру',
            'page_register' => 'Тіркелу',
            'status_not_submitted' => 'Тапсырылмады',
            'status_submitted' => 'Тексерілуде',
            'status_reviewed' => 'Тексерілді',
            'status_needs_revision' => 'Толықтыру қажет',
            'hero_badge' => 'Оқу платформасы',
            'hero_title' => 'Мерзімдер, курстар және тапсырмалар бір кеңістікте',
            'hero_text' => 'Тапсырмаларыңызды, курстарыңызды және тексеру күйлерін көру үшін кіріңіз.',
            'current_tasks' => 'Ағымдағы тапсырмаларыңыз',
            'add_task' => '+ Жаңа тапсырма қосу',
            'csrf_error' => 'Форма сессиясы ескірді. Әрекетті қайталаңыз.',
            'flash_success' => 'Дайын',
            'flash_error' => 'Қате',
            'login_success' => 'Жүйеге кірдіңіз.',
            'register_success' => 'Аккаунт жасалды.',
            'task_created' => 'Тапсырма жасалды.',
            'task_updated' => 'Тапсырма жаңартылды.',
            'task_deleted' => 'Тапсырма жойылды.',
            'work_submitted' => 'Жұмыс жіберілді.',
            'review_saved' => 'Тексеру сақталды.',
            'course_created' => 'Курс жасалды.',
            'group_created' => 'Топ жасалды.',
            'deadline_overview_text' => 'Басымдықтарды тез көру үшін тапсырмаларды курс, мерзім және күй бойынша сүзгілеңіз.',
            'deadline_metrics' => 'Мерзімдер қорытындысы',
            'metric_total' => 'Барлық тапсырмалар',
            'metric_overdue' => 'Мерзімі өтті',
            'metric_urgent' => 'Шұғыл',
            'metric_submitted' => 'Тапсырылды',
            'metric_planned' => 'Уақыт бар',
            'filter_search' => 'Іздеу',
            'filter_search_placeholder' => 'Атауы, сипаттамасы немесе курсы',
            'filter_course' => 'Курс',
            'filter_all_courses' => 'Барлық курстар',
            'filter_urgency' => 'Шұғылдық',
            'filter_all_deadlines' => 'Барлық мерзімдер',
            'filter_progress' => 'Тапсыру күйі',
            'filter_all_statuses' => 'Барлық күйлер',
            'apply_filters' => 'Көрсету',
            'reset_filters' => 'Тазарту',
            'week_deadlines' => 'Келесі 7 күн',
            'deadline_overdue' => 'Мерзімі өтті',
            'deadline_urgent' => 'Шұғыл',
            'deadline_normal' => 'Уақыт бар',
            'task_progress' => 'Күйі',
            'submission_count' => 'Тапсырулар',
            'empty_tasks_title' => 'Тапсырмалар табылмады',
            'empty_tasks_text' => 'Сүзгілерді өзгертіңіз немесе курсқа жаңа тапсырма жасаңыз.',
        ],
        'en' => [
            'app_name' => 'Deadline Hub',
            'workspace_label' => 'Workspace',
            'workspace_subtitle' => 'Learning workspace',
            'dashboard' => 'Workspace',
            'deadlines' => 'Deadlines',
            'courses' => 'Courses',
            'my_courses' => 'My courses',
            'groups' => 'Groups',
            'admin' => 'Admin',
            'about' => 'About',
            'contacts' => 'Contacts',
            'login' => 'Sign in',
            'logout' => 'Sign out',
            'theme' => 'Theme',
            'theme_light' => 'Light',
            'theme_dark' => 'Dark',
            'language' => 'Language',
            'student' => 'Student',
            'teacher' => 'Teacher',
            'administrator' => 'Administrator',
            'page_deadlines' => 'Deadlines',
            'page_courses' => 'Courses',
            'page_my_courses' => 'My courses',
            'page_groups' => 'Study groups',
            'page_admin' => 'Administration',
            'page_task' => 'Assignment',
            'page_add_task' => 'New assignment',
            'page_edit_task' => 'Edit assignment',
            'page_delete_task' => 'Delete assignment',
            'page_add_course' => 'New course',
            'page_add_group' => 'New group',
            'page_add_material' => 'New material',
            'page_add_task_attachment' => 'New assignment file',
            'page_about' => 'About',
            'page_contact' => 'Contacts',
            'page_login' => 'Sign in',
            'page_register' => 'Sign up',
            'status_not_submitted' => 'Not submitted',
            'status_submitted' => 'Submitted',
            'status_reviewed' => 'Reviewed',
            'status_needs_revision' => 'Needs revision',
            'hero_badge' => 'Learning platform',
            'hero_title' => 'Deadlines, courses, and submissions in one workspace',
            'hero_text' => 'Sign in to see your assignments, courses, and review statuses.',
            'current_tasks' => 'Your current assignments',
            'add_task' => '+ Add new assignment',
            'csrf_error' => 'The form session expired. Please try again.',
            'flash_success' => 'Done',
            'flash_error' => 'Error',
            'login_success' => 'You are signed in.',
            'register_success' => 'Account created.',
            'task_created' => 'Assignment created.',
            'task_updated' => 'Assignment updated.',
            'task_deleted' => 'Assignment deleted.',
            'work_submitted' => 'Work submitted.',
            'review_saved' => 'Review saved.',
            'course_created' => 'Course created.',
            'group_created' => 'Group created.',
            'deadline_overview_text' => 'Filter assignments by course, urgency, and progress to see priorities faster.',
            'deadline_metrics' => 'Deadline summary',
            'metric_total' => 'Total assignments',
            'metric_overdue' => 'Overdue',
            'metric_urgent' => 'Urgent',
            'metric_submitted' => 'Submitted',
            'metric_planned' => 'On track',
            'filter_search' => 'Search',
            'filter_search_placeholder' => 'Title, description, or course',
            'filter_course' => 'Course',
            'filter_all_courses' => 'All courses',
            'filter_urgency' => 'Urgency',
            'filter_all_deadlines' => 'All deadlines',
            'filter_progress' => 'Submission status',
            'filter_all_statuses' => 'All statuses',
            'apply_filters' => 'Apply',
            'reset_filters' => 'Reset',
            'week_deadlines' => 'Next 7 days',
            'deadline_overdue' => 'Overdue',
            'deadline_urgent' => 'Urgent',
            'deadline_normal' => 'On track',
            'task_progress' => 'Status',
            'submission_count' => 'Submissions',
            'empty_tasks_title' => 'No assignments found',
            'empty_tasks_text' => 'Change filters or create a new assignment for a course.',
        ],
    ];

    $language = currentLanguage();

    return $translations[$language][$key] ?? $translations['ru'][$key] ?? $key;
}

function currentUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireRole(string $role): void
{
    if (!isLoggedIn() || currentUserRole() !== $role) {
        header('Location: login.php');
        exit;
    }
}

function requireAnyRole(array $roles): void
{
    if (!isLoggedIn() || !in_array(currentUserRole(), $roles, true)) {
        header('Location: login.php');
        exit;
    }
}

function taskStatusLabel(string $status): string
{
    $labels = [
        'not_submitted' => t('status_not_submitted'),
        'submitted' => t('status_submitted'),
        'reviewed' => t('status_reviewed'),
        'needs_revision' => t('status_needs_revision'),
    ];

    return $labels[$status] ?? $status;
}
