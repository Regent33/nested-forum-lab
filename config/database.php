<?php
// Database configuration. Credentials come from environment variables,
// so nothing secret is committed to the repo.

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn  = getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=nested_forum;charset=utf8mb4';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        ]);
        $pdo->exec("SET time_zone = '+00:00'"); // timestamps are shown in UTC
    }
    return $pdo;
}
