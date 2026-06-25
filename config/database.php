<?php

declare(strict_types=1);

function getDatabase(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseDir = __DIR__ . '/../storage';
    if (!is_dir($databaseDir)) {
        mkdir($databaseDir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $databaseDir . '/app.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    initializeDatabase($pdo);

    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL CHECK (role IN ('student', 'teacher', 'admin')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS student_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS courses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            teacher_id INTEGER,
            group_id INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (group_id) REFERENCES student_groups(id) ON DELETE SET NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS group_students (
            group_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (group_id, user_id),
            FOREIGN KEY (group_id) REFERENCES student_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_students (
            course_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (course_id, user_id),
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_groups (
            course_id INTEGER NOT NULL,
            group_id INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (course_id, group_id),
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (group_id) REFERENCES student_groups(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_teachers (
            course_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role TEXT NOT NULL DEFAULT 'teacher',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (course_id, user_id),
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            deadline TEXT NOT NULL,
            course_id INTEGER,
            created_by INTEGER,
            assigned_role TEXT NOT NULL DEFAULT 'student',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS task_submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'submitted' CHECK (status IN ('submitted', 'reviewed', 'needs_revision')),
            grade TEXT,
            feedback TEXT,
            submitted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TEXT,
            UNIQUE (task_id, user_id),
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_materials (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            uploaded_by INTEGER,
            title TEXT NOT NULL,
            original_name TEXT NOT NULL,
            stored_name TEXT NOT NULL,
            mime_type TEXT NOT NULL,
            file_size INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS task_attachments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id INTEGER NOT NULL,
            uploaded_by INTEGER,
            title TEXT NOT NULL,
            original_name TEXT NOT NULL,
            stored_name TEXT NOT NULL,
            mime_type TEXT NOT NULL,
            file_size INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_announcements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id INTEGER NOT NULL,
            author_id INTEGER,
            title TEXT NOT NULL,
            body TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS task_comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id INTEGER NOT NULL,
            author_id INTEGER,
            body TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            title TEXT NOT NULL,
            body TEXT NOT NULL DEFAULT '',
            url TEXT NOT NULL DEFAULT '',
            read_at TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    addColumnIfMissing($pdo, 'tasks', 'course_id', 'INTEGER');
    migrateCourseAccess($pdo);

    seedInitialData($pdo);
    migrateCourseAccess($pdo);
}

function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
{
    $columns = $pdo->query("PRAGMA table_info($table)")->fetchAll();
    foreach ($columns as $existingColumn) {
        if ($existingColumn['name'] === $column) {
            return;
        }
    }

    $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
}

function migrateCourseAccess(PDO $pdo): void
{
    $pdo->exec('
        INSERT OR IGNORE INTO course_groups (course_id, group_id)
        SELECT id, group_id
        FROM courses
        WHERE group_id IS NOT NULL
    ');

    $pdo->exec('
        INSERT OR IGNORE INTO course_teachers (course_id, user_id, role)
        SELECT id, teacher_id, "teacher"
        FROM courses
        WHERE teacher_id IS NOT NULL
    ');

    $pdo->exec('
        INSERT OR IGNORE INTO course_students (course_id, user_id)
        SELECT course_groups.course_id, group_students.user_id
        FROM course_groups
        INNER JOIN group_students ON group_students.group_id = course_groups.group_id
    ');
}

function seedInitialData(PDO $pdo): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $users = [
            ['stud', '111', 'Иван Студент', 'student'],
            ['prep', '222', 'Анна Преподаватель', 'teacher'],
            ['admin', '333', 'Администратор', 'admin'],
        ];

        $statement = $pdo->prepare('
            INSERT INTO users (username, password_hash, full_name, role)
            VALUES (:username, :password_hash, :full_name, :role)
        ');

        foreach ($users as [$username, $password, $fullName, $role]) {
            $statement->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $fullName,
                'role' => $role,
            ]);
        }
    }

    $teacherId = (int) $pdo
        ->query("SELECT id FROM users WHERE username = 'prep'")
        ->fetchColumn();

    if ((int) $pdo->query('SELECT COUNT(*) FROM student_groups')->fetchColumn() === 0) {
        $pdo->prepare('
            INSERT INTO student_groups (name, description)
            VALUES (:name, :description)
        ')->execute([
            'name' => 'CS-101',
            'description' => 'Первая учебная группа для прототипа.',
        ]);
    }

    $groupId = (int) $pdo
        ->query("SELECT id FROM student_groups WHERE name = 'CS-101'")
        ->fetchColumn();

    $pdo->prepare('
        INSERT OR IGNORE INTO group_students (group_id, user_id)
        SELECT :group_id, id
        FROM users
        WHERE role = "student"
    ')->execute([
        'group_id' => $groupId,
    ]);

    if ((int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn() === 0) {
        $courseStatement = $pdo->prepare('
            INSERT INTO courses (title, description, teacher_id, group_id)
            VALUES (:title, :description, :teacher_id, :group_id)
        ');

        $courseStatement->execute([
            'title' => 'Математика',
            'description' => 'Практика, дедлайны и материалы по математике.',
            'teacher_id' => $teacherId,
            'group_id' => $groupId,
        ]);

        $courseStatement->execute([
            'title' => 'Базы данных',
            'description' => 'Проектирование схем, SQL и практические задания.',
            'teacher_id' => $teacherId,
            'group_id' => $groupId,
        ]);
    }

    $mathCourseId = (int) $pdo
        ->query("SELECT id FROM courses WHERE title = 'Математика'")
        ->fetchColumn();
    $databaseCourseId = (int) $pdo
        ->query("SELECT id FROM courses WHERE title = 'Базы данных'")
        ->fetchColumn();

    $enrollStatement = $pdo->prepare('
        INSERT OR IGNORE INTO course_students (course_id, user_id)
        VALUES (:course_id, :user_id)
    ');
    $studentIds = $pdo
        ->query('SELECT user_id FROM group_students WHERE group_id = ' . $groupId)
        ->fetchAll();
    foreach ([$mathCourseId, $databaseCourseId] as $courseId) {
        foreach ($studentIds as $student) {
            $enrollStatement->execute([
                'course_id' => $courseId,
                'user_id' => (int) $student['user_id'],
            ]);
        }
    }

    if ($mathCourseId) {
        $pdo->prepare('UPDATE tasks SET course_id = :course_id WHERE course_id IS NULL')
            ->execute(['course_id' => $mathCourseId]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn() === 0) {
        $tasks = [
            ['Курсовая по интегралам', 'Курсовая работа по интегралам.', '2026-06-24', $mathCourseId],
            ['Схема users', 'Спроектировать схему таблицы users.', '2026-06-29', $databaseCourseId],
            ['SQL-запросы', 'Подготовить 10 SQL-запросов к учебной базе.', '2026-07-10', $databaseCourseId],
        ];

        $taskStatement = $pdo->prepare('
            INSERT INTO tasks (title, description, deadline, course_id, created_by)
            VALUES (:title, :description, :deadline, :course_id, :created_by)
        ');

        foreach ($tasks as [$title, $description, $deadline, $courseId]) {
            $taskStatement->execute([
                'title' => $title,
                'description' => $description,
                'deadline' => $deadline,
                'course_id' => $courseId,
                'created_by' => $teacherId,
            ]);
        }
    }
}
