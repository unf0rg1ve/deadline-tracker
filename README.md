# Deadline Hub

Deadline Hub is a PHP + SQLite learning workspace for students, teachers, and administrators. The system helps manage courses, study groups, assignments, course materials, submissions, reviews, comments, and notifications.

## Features

- Role-based access for students, teachers, and administrators.
- Course management with support for multiple teachers and multiple study groups per course.
- Student group management and student-to-group assignment.
- Assignment deadlines with filters, urgency status, and dashboard metrics.
- Course materials and assignment attachments.
- Student submissions and teacher reviews.
- Course announcements, task comments, and activity feed.
- Notification center for new tasks, materials, announcements, submissions, reviews, and comments.
- CSRF protection for POST forms.
- Light/dark theme switcher and RU/KZ/EN language switcher.
- SQLite database auto-initialization and seed data.

## Tech Stack

- PHP 7.4+
- SQLite with PDO
- HTML/CSS/Vanilla JS
- OpenServer/OSPanel friendly local setup

## Local Setup

1. Put the project into an OpenServer/OSPanel host directory, for example:

   ```text
   C:\OSPanel\home\tasktracker.local\public
   ```

2. Make sure PHP extensions are enabled:

   - `pdo_sqlite`
   - `sqlite3`

3. Open the project in the browser:

   ```text
   http://tasktracker.local/public/
   ```

   If your OSPanel host points directly to the `public` directory, use:

   ```text
   http://tasktracker.local/
   ```

4. The database is created automatically at:

   ```text
   storage/app.sqlite
   ```

## Test Accounts

The app seeds demo users on first run:

| Role | Login | Password |
| --- | --- | --- |
| Student | `stud` | `111` |
| Teacher | `prep` | `222` |
| Admin | `admin` | `333` |

## Access Model

- Students see only courses where they are enrolled through `course_students`.
- Teachers see only courses where they are assigned through `course_teachers`.
- Admins can manage all courses, groups, students, and assignments.
- Courses can be connected to multiple groups through `course_groups`.
- Courses can have multiple teachers through `course_teachers`.

## Main Project Structure

```text
config/database.php       SQLite connection, schema, migrations, seed data
models/                   Data access and domain logic
index.php                 Deadline dashboard
courses.php               Course list
course.php                Course page, feed, materials, announcements
task.php                  Assignment page, submissions, comments
students.php              Admin student management
groups.php                Admin group management
notifications.php         Notification center
style.css                 Main UI system
```

## Security Notes

- All state-changing forms use CSRF tokens.
- Uploaded files are validated by size and extension.
- Runtime SQLite database and uploaded files are ignored by Git.
- Do not commit real API keys, `.env` files, or production databases.

## Roadmap

- Real-time chat and course channels.
- Calendar view for deadlines.
- Fine-grained teacher roles such as assistant, reviewer, and owner.
- Admin import/export for students and groups.
- Production deployment profile with environment-based configuration.
