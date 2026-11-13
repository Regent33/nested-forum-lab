<?php
namespace App\Controllers;

use App\Models\Comment;
use App\Models\Discussion;

class DiscussionController extends Controller
{
    const INDENT_CAP = 8;    // indentation stops growing after depth 8
    const MAX_NODES  = 1000; // traversal limit for one page

    public function index(): void
    {
        $this->render('index', ['discussions' => Discussion::all()]);
    }

    // GET /discussions/{id}
    public function show(string $rawId): void
    {
        $id = self::parseId($rawId);
        if ($id === null) {
            $this->error(400, 'That discussion ID is not valid.');
            return;
        }
        $discussion = Discussion::find($id);
        if ($discussion === null) {
            $this->error(404, 'Discussion not found.');
            return;
        }

        $comments = self::buildTree(Comment::forDiscussion($id));
        $this->render('discussion', [
            'discussion' => $discussion,
            'comments'   => $comments,
            'count'      => count($comments),
        ]);
    }

    /**
     * Groups comments by parent_id and returns them in reading order,
     * each with a calculated depth (top-level = 0) and a capped indent.
     */
    public static function buildTree(array $rows, int $maxNodes = self::MAX_NODES): array
    {
        $children = [];
        foreach ($rows as $row) {
            $children[$row['parent_id'] ?? 0][] = $row; // key 0 = top-level
        }

        $out = [];
        $seen = [];
        $stack = []; // [row, depth], walked depth-first without recursion
        foreach (array_reverse($children[0] ?? []) as $row) {
            $stack[] = [$row, 0];
        }

        while ($stack && count($out) < $maxNodes) {
            [$row, $depth] = array_pop($stack);
            if (isset($seen[$row['id']])) {
                continue; // guard against bad data: never show a comment twice
            }
            $seen[$row['id']] = true;

            $row['depth'] = $depth;
            $row['indent'] = min($depth, self::INDENT_CAP);
            $out[] = $row;

            foreach (array_reverse($children[$row['id']] ?? []) as $kid) {
                $stack[] = [$kid, $depth + 1];
            }
        }
        return $out;
    }
}
