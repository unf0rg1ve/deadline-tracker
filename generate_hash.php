<?php
$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Пароль: 123456</h2>";
echo "<h3>Хэш:</h3>";
echo "<pre>" . $hash . "</pre>";
echo "<p>Скопируй этот хэш и вставь в SQL ниже.</p>";
?>