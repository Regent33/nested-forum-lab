<?php
namespace App\Controllers;

use App\Models\Comment;
use App\Models\Discussion;

class CommentController extends Controller
{
    const MAX_LENGTH = 2000;
    const MAX_DEPTH  = 50; // deepest displayed depth a new reply may have

    // POST /discussions/{id}/comments   fields: body, parent_id (optional), csrf
    public function store(string $rawId): void
    {
        // 1. Must be logged in (checked on the server, not just hidden in the view).
        $userId = $this->userId();
        if ($userId === null) {
            $this->redirect('/login', 'Please log in to post a reply.');
            return;
        }

        // 2. CSRF token must match the one in the session.
        if (!$this->csrfValid()) {
            $this->error(403, 'Invalid or expired form. Reload the page and try again.');
            return;
        }

        $discussionId = self::parseId($rawId);
        if ($discussionId === null || Discussion::find($discussionId) === null) {
            $this->error(404, 'Discussion not found.');
            return;
        }
        $back = "/discussions/$discussionId";

        // 3. Validate the text.
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > self::MAX_LENGTH) {
            $this->redirect($back, 'A reply must be 1 to ' . self::MAX_LENGTH . ' characters.');
            return;
        }

        // 4. Parent: empty means top-level, otherwise it must be in THIS discussion.
        $parentId = null;
        $rawParent = (string) ($_POST['parent_id'] ?? '');
        if ($rawParent !== '') {
            $parentId = self::parseId($rawParent);
            $parentDepth = $parentId === null ? null : Comment::depthOf($parentId, $discussionId, self::MAX_DEPTH);
            if ($parentDepth === null) {
                $this->error(422, 'You can only reply to a comment in this discussion.');
                return;
            }
            if ($parentDepth + 1 > self::MAX_DEPTH) {
                $this->redirect($back, 'This thread is too deep to reply to.');
                return;
            }
        }

        // 5. Save with the session user ID (never from the form) and redirect.
        // There is no edit route, so parent links never change and cannot form a cycle.
        $newId = Comment::create($discussionId, $parentId, $userId, $body);
        $this->redirect("$back#c$newId");
    }
}
