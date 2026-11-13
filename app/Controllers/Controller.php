<?php
namespace App\Controllers;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

// Shared helpers for every controller: rendering, redirects, login state and CSRF.
abstract class Controller
{
    protected function render(string $template, array $data = [], int $status = 200): void
    {
        static $mustache = null;
        $mustache ??= new Mustache_Engine([
            'loader'          => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../Views'),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/../Views'),
            'entity_flags'    => ENT_QUOTES, // {{var}} is always HTML-escaped
        ]);

        // Every page needs these for the header and its forms.
        $data += [
            'me'    => isset($_SESSION['username']) ? ['username' => $_SESSION['username']] : null,
            'csrf'  => $this->csrfToken(),
            'flash' => $_SESSION['flash'] ?? null,
        ];
        unset($_SESSION['flash']);

        http_response_code($status);
        echo $mustache->render($template, $data);
    }

    protected function error(int $status, string $message): void
    {
        $this->render('error', ['status' => $status, 'message' => $message], $status);
    }

    public function notFound(): void
    {
        $this->error(404, 'Page not found.');
    }

    protected function redirect(string $path, ?string $flash = null): void
    {
        if ($flash !== null) {
            $_SESSION['flash'] = $flash;
        }
        header('Location: ' . $path, true, 303);
    }

    protected function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    protected function startSession(int $id, string $username): void
    {
        session_regenerate_id(true); // new session ID after login
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
        unset($_SESSION['csrf']);
    }

    protected function csrfToken(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    protected function csrfValid(): bool
    {
        $sent = $_POST['csrf'] ?? '';
        return is_string($sent) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
    }

    /** Positive whole number that fits an INT UNSIGNED column, or null. */
    public static function parseId(string $raw): ?int
    {
        if (!preg_match('/^[1-9][0-9]{0,9}$/', $raw) || (int) $raw > 4294967295) {
            return null;
        }
        return (int) $raw;
    }
}
