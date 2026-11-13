# Nested Forum Replies (PHP, MySQL, PDO)

A small forum that shows replies as a tree. Anyone can read a discussion. Only logged-in users can post.

## Libraries I used

| Job | Library | Why |
|---|---|---|
| Routing | `bramus/router` | Lecture library, one front controller in `public/index.php` |
| Data access | plain PDO (prepared statements) | Instead of Eloquent. It is less setup for three tables, and all SQL stays inside `app/Models` |
| Templates | `mustache/mustache` | Lecture library. `{{var}}` escapes HTML by default |

## Setup

Requirements: PHP 8.2+ with `pdo_mysql`, MySQL 8 (or MariaDB), Composer.

1. Install dependencies:
   ```bash
   composer install
   ```
2. Enable `pdo_mysql`: in `php.ini`, remove the `;` in front of `extension=pdo_mysql`.
3. Create the database and seed data:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
   I ran MySQL in Docker:
   ```bash
   docker run -d --name forum-mysql -e MYSQL_ROOT_PASSWORD=<your password> -p 3306:3306 mysql:8
   docker exec -i forum-mysql mysql -uroot -p<your password> < database/schema.sql
   ```
4. Set your database login as environment variables (nothing secret is in the repo). Defaults are `root`, an empty password and `127.0.0.1:3306/nested_forum`.
   ```bash
   set DB_USER=root
   set DB_PASS=<your password>
   ```
5. Run the built-in server and open http://localhost:8000/discussions/1
   ```bash
   php -S localhost:8000 -t public public/index.php
   ```
   On Apache or Nginx you would point the document root at `public/` and send every request that isn't a real file to `index.php`. The local test doesn't need that.

## Routes

| Method | Path | Controller |
|---|---|---|
| GET | `/` | DiscussionController@index |
| GET | `/discussions/{id}` | DiscussionController@show |
| POST | `/discussions/{id}/comments` | CommentController@store |
| GET/POST | `/register` | AuthController |
| GET/POST | `/login` | AuthController |
| POST | `/logout` | AuthController |

A malformed ID like `/discussions/abc` returns **400**. A valid ID that doesn't exist, like `/discussions/999`, returns **404**.

## How the tree works

`discussion_id` says which discussion a comment belongs to. `parent_id` says which comment it answers, and `NULL` means it is a top-level comment. The composite foreign key `(discussion_id, parent_id) → comments(discussion_id, id)` stops a reply from pointing at a comment in another discussion.

`DiscussionController::buildTree()` groups the rows by `parent_id` and walks them depth first. Depth is calculated during that walk, not stored. Indentation stops at depth 8, but the "Depth N" label still shows the real number. The walk stops after 1000 comments, and it skips any comment it has already printed, so bad data can't make it loop forever.

There is no route that edits a comment's parent. New comments can only point at a comment that already exists, so the app itself can't create a cycle. `Comment::depthOf()` also refuses to reply under a comment whose parent chain loops or goes deeper than 50.

## Tests

Tree logic (no database needed):
```bash
php tests/CommentTreeTest.php
```

Manual checks I ran against the running server (screenshots are in my submission doc):

1. Register, log in, reply under comment 7 (depth 5), and add a second top-level comment.
2. Log out and post through the form → redirected to login, no row added.
3. Log out and send a direct POST with curl → no row added:
   `curl -i -X POST -d "body=hi" http://localhost:8000/discussions/1/comments`
4. Send a wrong CSRF token → 403.
5. Log in with a wrong password → "Wrong username or password".
6. Use a `parent_id` from discussion 2 while posting to discussion 1 → 422.
7. Open `/discussions/999` → 404, and `/discussions/abc` → 400.
8. Post `<script>alert(1)</script>` → it shows as plain text because Mustache escapes it.

The seed users (alice, ben, cara, dee, eli) have a `NULL` password hash, so nobody can log in as them. Register your own account.
