<?php 
 include 'header.php';
 require_once __DIR__ . '/models/Task.php';
 require_once __DIR__ . '/models/Course.php';
 error_reporting(E_ALL);
 ini_set('display_errors', 1);
 
 $allTasks = isLoggedIn()
    ? Task::forUser((int) $_SESSION['user_id'], $_SESSION['role'])
    : [];
 $courses = isLoggedIn()
    ? Course::forUser((int) $_SESSION['user_id'], $_SESSION['role'])
    : [];

 $today = date('Y-m-d');
 $current_time = strtotime(date("Y-m-d"));
 $selectedCourse = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
 $selectedUrgency = $_GET['urgency'] ?? 'all';
 $selectedProgress = $_GET['progress'] ?? 'all';
 $search = trim((string) ($_GET['q'] ?? ''));
 $allowedUrgencies = ['all', 'overdue', 'urgent', 'normal'];
 $allowedProgress = ['all', 'not_submitted', 'submitted', 'reviewed', 'needs_revision'];

 if (!in_array($selectedUrgency, $allowedUrgencies, true)) {
    $selectedUrgency = 'all';
 }

 if (!in_array($selectedProgress, $allowedProgress, true)) {
    $selectedProgress = 'all';
 }

 $tasks = [];
 $dashboardStats = [
    'total' => 0,
    'overdue' => 0,
    'urgent' => 0,
    'normal' => 0,
    'submitted' => 0,
 ];

 foreach ($allTasks as $task) {
    $deadlineTime = strtotime($task['deadline']);
    $diff = ($deadlineTime - $current_time) / (60 * 60 * 24);
    $urgency = $diff < 0 ? 'overdue' : ($diff <= 3 ? 'urgent' : 'normal');
    $studentStatus = $_SESSION['role'] === 'student'
        ? Task::studentStatus((int) $task['id'], (int) $_SESSION['user_id'])
        : null;

    $task['_deadline_time'] = $deadlineTime;
    $task['_urgency'] = $urgency;
    $task['_student_status'] = $studentStatus;
    $task['_submission_count'] = $_SESSION['role'] !== 'student' ? Task::submissionCount((int) $task['id']) : 0;

    $dashboardStats['total']++;
    $dashboardStats[$urgency]++;

    if ($studentStatus !== null && $studentStatus !== 'not_submitted') {
        $dashboardStats['submitted']++;
    }

    if ($selectedCourse > 0 && (int) $task['course_id'] !== $selectedCourse) {
        continue;
    }

    if ($selectedUrgency !== 'all' && $urgency !== $selectedUrgency) {
        continue;
    }

    if ($studentStatus !== null && $selectedProgress !== 'all' && $studentStatus !== $selectedProgress) {
        continue;
    }

    if ($search !== '') {
        $lower = static function (string $value): string {
            return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        };
        $haystack = $lower(($task['title'] ?? '') . ' ' . ($task['description'] ?? '') . ' ' . ($task['course_title'] ?? ''));
        if (strpos($haystack, $lower($search)) === false) {
            continue;
        }
    }

    $tasks[] = $task;
 }

 $weekTasks = array_values(array_filter($allTasks, static function (array $task) use ($current_time): bool {
    $deadlineTime = strtotime($task['deadline']);
    $diff = ($deadlineTime - $current_time) / (60 * 60 * 24);

    return $diff >= 0 && $diff <= 7;
 }));
 ?>

<main>
    <?php if (!isset($_SESSION['role'])): ?>
        
        <div class="welcome-block">
            <span class="status-label"><?php echo e(t('hero_badge')); ?></span>
            <h2><?php echo e(t('hero_title')); ?></h2>
            <p><?php echo e(t('hero_text')); ?></p>
            <p><a href="login.php" class="btn-login"><?php echo e(t('login')); ?></a></p>
        </div>

    <?php else: ?>
        
        <section class="dashboard-head">
            <div>
                <span class="status-label"><?php echo e(t('dashboard')); ?></span>
                <h2><?php echo e(t('current_tasks')); ?></h2>
                <p><?php echo e(t('deadline_overview_text')); ?></p>
            </div>
            <?php if ($_SESSION['role'] === 'teacher' || $_SESSION['role'] === 'admin'): ?>
                <a href="add_task.php" class="btn-login"><?php echo e(t('add_task')); ?></a>
            <?php endif; ?>
        </section>

        <section class="metric-grid" aria-label="<?php echo e(t('deadline_metrics')); ?>">
            <div class="metric-card">
                <span><?php echo e(t('metric_total')); ?></span>
                <strong><?php echo (int) $dashboardStats['total']; ?></strong>
            </div>
            <div class="metric-card metric-danger">
                <span><?php echo e(t('metric_overdue')); ?></span>
                <strong><?php echo (int) $dashboardStats['overdue']; ?></strong>
            </div>
            <div class="metric-card metric-warning">
                <span><?php echo e(t('metric_urgent')); ?></span>
                <strong><?php echo (int) $dashboardStats['urgent']; ?></strong>
            </div>
            <div class="metric-card metric-success">
                <span><?php echo e($_SESSION['role'] === 'student' ? t('metric_submitted') : t('metric_planned')); ?></span>
                <strong><?php echo (int) ($_SESSION['role'] === 'student' ? $dashboardStats['submitted'] : $dashboardStats['normal']); ?></strong>
            </div>
        </section>

        <form class="filter-bar" method="get">
            <label>
                <span><?php echo e(t('filter_search')); ?></span>
                <input type="search" name="q" value="<?php echo e($search); ?>" placeholder="<?php echo e(t('filter_search_placeholder')); ?>">
            </label>
            <label>
                <span><?php echo e(t('filter_course')); ?></span>
                <select name="course_id">
                    <option value="0"><?php echo e(t('filter_all_courses')); ?></option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo (int) $course['id']; ?>" <?php echo $selectedCourse === (int) $course['id'] ? 'selected' : ''; ?>>
                            <?php echo e($course['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php echo e(t('filter_urgency')); ?></span>
                <select name="urgency">
                    <option value="all" <?php echo $selectedUrgency === 'all' ? 'selected' : ''; ?>><?php echo e(t('filter_all_deadlines')); ?></option>
                    <option value="overdue" <?php echo $selectedUrgency === 'overdue' ? 'selected' : ''; ?>><?php echo e(t('deadline_overdue')); ?></option>
                    <option value="urgent" <?php echo $selectedUrgency === 'urgent' ? 'selected' : ''; ?>><?php echo e(t('deadline_urgent')); ?></option>
                    <option value="normal" <?php echo $selectedUrgency === 'normal' ? 'selected' : ''; ?>><?php echo e(t('deadline_normal')); ?></option>
                </select>
            </label>
            <?php if ($_SESSION['role'] === 'student'): ?>
                <label>
                    <span><?php echo e(t('filter_progress')); ?></span>
                    <select name="progress">
                        <option value="all" <?php echo $selectedProgress === 'all' ? 'selected' : ''; ?>><?php echo e(t('filter_all_statuses')); ?></option>
                        <?php foreach (['not_submitted', 'submitted', 'reviewed', 'needs_revision'] as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $selectedProgress === $status ? 'selected' : ''; ?>>
                                <?php echo e(taskStatusLabel($status)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <div class="filter-actions">
                <button type="submit"><?php echo e(t('apply_filters')); ?></button>
                <a href="index.php" class="button button-ghost"><?php echo e(t('reset_filters')); ?></a>
            </div>
        </form>

        <?php if (!empty($weekTasks)): ?>
            <section class="week-strip" aria-label="<?php echo e(t('week_deadlines')); ?>">
                <div class="section-header">
                    <h2><?php echo e(t('week_deadlines')); ?></h2>
                </div>
                <div class="week-list">
                    <?php foreach (array_slice($weekTasks, 0, 6) as $task): ?>
                        <a class="week-item" href="task.php?id=<?php echo (int) $task['id']; ?>">
                            <span><?php echo date("d.m", strtotime($task['deadline'])); ?></span>
                            <strong><?php echo e($task['title']); ?></strong>
                            <small><?php echo e($task['course_title'] ?? ''); ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
        <div class="cards-container">
            <?php foreach ($tasks as $task): 
                $deadline_time = $task['_deadline_time'];
                if ($task['_urgency'] === 'overdue') {
                    $status_class = 'status-critical';
                    $label = t('deadline_overdue');
                } elseif ($task['_urgency'] === 'urgent') {
                    $status_class = 'status-warning';
                    $label = t('deadline_urgent');
                } else {
                    $status_class = 'status-normal';
                    $label = t('deadline_normal');
                }
            ?>
                <div class="card <?php echo $status_class; ?>">
                    <span class="status-label"><?php echo $label; ?></span>
                    <h3><a href="task.php?id=<?php echo (int) $task['id']; ?>"><?php echo e($task['title']); ?></a></h3>
                    <p><?php echo e($task['description']); ?></p>
                    <small>Дедлайн: <?php echo date("d.m.Y", $deadline_time); ?></small>
                    <?php if (!empty($task['course_title'])): ?>
                        <small>Курс: <?php echo e($task['course_title']); ?></small>
                    <?php endif; ?>
                    <?php if (!empty($task['creator_name'])): ?>
                        <small>Автор: <?php echo e($task['creator_name']); ?></small>
                    <?php endif; ?>
                    <?php if ($_SESSION['role'] === 'student'): ?>
                        <small><?php echo e(t('task_progress')); ?>: <?php echo e(taskStatusLabel($task['_student_status'])); ?></small>
                    <?php else: ?>
                        <small><?php echo e(t('submission_count')); ?>: <?php echo (int) $task['_submission_count']; ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <strong><?php echo e(t('empty_tasks_title')); ?></strong>
                <p><?php echo e(t('empty_tasks_text')); ?></p>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>
