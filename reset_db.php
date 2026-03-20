<?php

// Script to drop and recreate the MySQL database specified in .env

$dotenv = __DIR__ . '/.env';
if (!file_exists($dotenv)) {
    die('.env file not found');
}

// Load .env manually
$lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    [$key, $val] = explode('=', $line, 2);
    putenv(trim($key) . '=' . trim($val));
}

$db = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD');
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';

$pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("DROP DATABASE IF EXISTS `$db`");
$pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

echo "Database $db reset.\n";
