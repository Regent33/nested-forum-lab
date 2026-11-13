<?php
namespace App\Models;

final class Discussion
{
    public static function all(): array
    {
        return db()->query(
            'SELECT d.id, d.title, d.created_at, u.username AS author
             FROM discussions d JOIN users u ON u.id = d.user_id
             ORDER BY d.created_at DESC, d.id DESC'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT d.id, d.title, d.body, d.created_at, u.username AS author
             FROM discussions d JOIN users u ON u.id = d.user_id
             WHERE d.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
