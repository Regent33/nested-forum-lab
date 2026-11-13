<?php
namespace App\Models;

final class Comment
{
    /** All comments of one discussion with their author, oldest first. */
    public static function forDiscussion(int $discussionId): array
    {
        $stmt = db()->prepare(
            'SELECT c.id, c.parent_id, c.body, c.created_at, u.username AS author
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.discussion_id = ?
             ORDER BY c.created_at, c.id'
        );
        $stmt->execute([$discussionId]);
        return $stmt->fetchAll();
    }

    /** Parent id of a comment, but only if it belongs to the given discussion. */
    public static function parentIn(int $commentId, int $discussionId): array|false
    {
        $stmt = db()->prepare(
            'SELECT parent_id FROM comments WHERE id = ? AND discussion_id = ?'
        );
        $stmt->execute([$commentId, $discussionId]);
        return $stmt->fetch();
    }

    /**
     * Displayed depth of an existing comment (top-level = 0), walking up parent links.
     * Returns null if the comment is missing, in another discussion, or the walk
     * loops / runs past $limit (bad data), so callers can refuse to reply to it.
     */
    public static function depthOf(int $commentId, int $discussionId, int $limit): ?int
    {
        $seen = [];
        $depth = -1;
        $current = $commentId;
        while ($current !== null) {
            if (isset($seen[$current]) || $depth >= $limit) {
                return null;
            }
            $seen[$current] = true;
            $row = self::parentIn($current, $discussionId);
            if ($row === false) {
                return null;
            }
            $current = $row['parent_id'] === null ? null : (int) $row['parent_id'];
            $depth++;
        }
        return $depth;
    }

    public static function create(int $discussionId, ?int $parentId, int $userId, string $body): int
    {
        $pdo = db();
        $stmt = $pdo->prepare(
            'INSERT INTO comments (discussion_id, parent_id, user_id, body) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$discussionId, $parentId, $userId, $body]);
        return (int) $pdo->lastInsertId();
    }
}
