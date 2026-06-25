<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowedLanguages = ['ru', 'kk', 'en'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLanguages, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'ru';
}

require_once __DIR__ . '/helpers.php';
