<?php
namespace App\Models;

final class User
{
    public static function findByUsername(string $username): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, username, password_hash FROM users WHERE username = ?'
        );
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    public static function exists(string $username, string $email): bool
    {
        $stmt = db()->prepare(
            'SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$username, $email]);
        return (bool) $stmt->fetchColumn();
    }

    public static function create(string $username, string $email, string $passwordHash): int
    {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $passwordHash]);
        return (int) $pdo->lastInsertId();
    }
}
