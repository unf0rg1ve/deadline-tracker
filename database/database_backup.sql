
-- База данных: `deadline_tracker`
--
CREATE DATABASE IF NOT EXISTS `deadline_tracker` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `deadline_tracker`;

-- --------------------------------------------------------

--
-- Структура таблицы `disciplines`
--

CREATE TABLE `disciplines` (
  `id` int NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `disciplines`
--

INSERT INTO `disciplines` (`id`, `name`, `created_at`) VALUES
(1, 'Разработка и сопровождение ИС', '2026-04-25 13:28:03'),
(2, 'Проектирование информационных систем', '2026-04-25 13:28:03'),
(4, 'Мобильные технологии и приложения', '2026-04-25 13:28:03'),
(6, 'Создание информационных ресурсов', '2026-04-28 11:36:35');

-- --------------------------------------------------------

--
-- Структура таблицы `groups`
--

CREATE TABLE `groups` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `groups`
--

INSERT INTO `groups` (`id`, `name`, `created_at`) VALUES
(1, 'ИС-23-2', '2026-04-25 13:28:03'),
(3, 'Эконом-23-1', '2026-04-25 13:28:03');

-- --------------------------------------------------------

--
-- Структура таблицы `group_disciplines`
--

CREATE TABLE `group_disciplines` (
  `group_id` int NOT NULL,
  `discipline_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `group_disciplines`
--

INSERT INTO `group_disciplines` (`group_id`, `discipline_id`) VALUES
(1, 1),
(1, 2);

-- --------------------------------------------------------

--
-- Структура таблицы `messages`
--

CREATE TABLE `messages` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `subject` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('new','read','replied') COLLATE utf8mb4_general_ci DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `student_file_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deadline` date NOT NULL,
  `status` enum('в процессе','сдана','проверена') COLLATE utf8mb4_general_ci DEFAULT 'в процессе',
  `discipline_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `group_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `file_path`, `student_file_path`, `deadline`, `status`, `discipline_id`, `teacher_id`, `group_id`, `created_at`) VALUES
(1, 'Лабораторная работа №11', 'Задания для выполнения\r\n1.	Создайте кнопку, которая будет выводить всплывающее сообщение на экран при нажатии с помощью объекта Toast.\r\n2.	Создайте проигрыватель PlayService.java для воспроизведения и остановки одной песни.\r\n3.	Создайте приложение, которое будет отправлять и принимать сообщения.', NULL, NULL, '2026-04-29', 'в процессе', 4, 2, 1, '2026-04-26 12:33:00'),
(3, 'Лаб 0', '241413', NULL, NULL, '2026-04-30', 'в процессе', 4, 1, 1, '2026-04-29 09:17:31');

-- --------------------------------------------------------

--
-- Структура таблицы `teacher_disciplines`
--

CREATE TABLE `teacher_disciplines` (
  `teacher_id` int NOT NULL,
  `discipline_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `teacher_disciplines`
--

INSERT INTO `teacher_disciplines` (`teacher_id`, `discipline_id`) VALUES
(2, 1),
(2, 2),
(2, 4);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('student','teacher','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'student',
  `group_name` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `group_name`, `created_at`) VALUES
(1, 'Администратор', 'admin@demo.ru', '$2y$10$qPXjZlCI8YJIB.G8unn42eUNfogxbYKqeCjZCi9ENHASmk595R.NC', 'admin', NULL, '2026-04-25 13:28:03'),
(2, 'Пяткова Татьяна Владимировна', 'teacher1@demo.ru', '$2y$10$qPXjZlCI8YJIB.G8unn42eUNfogxbYKqeCjZCi9ENHASmk595R.NC', 'teacher', NULL, '2026-04-25 13:28:03'),
(3, 'Лукьянов Кирилл', 'student1@demo.ru', '$2y$10$qPXjZlCI8YJIB.G8unn42eUNfogxbYKqeCjZCi9ENHASmk595R.NC', 'student', 'ИС-23-2', '2026-04-25 13:28:03');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `disciplines`
--
ALTER TABLE `disciplines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `group_disciplines`
--
ALTER TABLE `group_disciplines`
  ADD PRIMARY KEY (`group_id`,`discipline_id`),
  ADD KEY `discipline_id` (`discipline_id`);

--
-- Индексы таблицы `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `discipline_id` (`discipline_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `group_id` (`group_id`);

--
-- Индексы таблицы `teacher_disciplines`
--
ALTER TABLE `teacher_disciplines`
  ADD PRIMARY KEY (`teacher_id`,`discipline_id`),
  ADD KEY `discipline_id` (`discipline_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `disciplines`
--
ALTER TABLE `disciplines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `group_disciplines`
--
ALTER TABLE `group_disciplines`
  ADD CONSTRAINT `group_disciplines_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_disciplines_ibfk_2` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `teacher_disciplines`
--
ALTER TABLE `teacher_disciplines`
  ADD CONSTRAINT `teacher_disciplines_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_disciplines_ibfk_2` FOREIGN KEY (`discipline_id`) REFERENCES `disciplines` (`id`) ON DELETE CASCADE;
--
