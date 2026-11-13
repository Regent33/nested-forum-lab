<?php
namespace App\Controllers;

use App\Models\User;
use PDOException;

class AuthController extends Controller
{
    public function registerForm(): void
    {
        $this->render('register');
    }

    public function register(): void
    {
        if (!$this->csrfValid()) {
            $this->error(403, 'Invalid or expired form. Reload the page and try again.');
            return;
        }
        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $error = null;
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $error = 'Username must be 3-30 letters, numbers or underscores.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($password) < 8 || strlen($password) > 72) {
            $error = 'Password must be 8 to 72 characters.';
        } elseif (User::exists($username, $email)) {
            $error = 'That username or email is already registered.';
        }
        if ($error !== null) {
            $this->render('register', ['error' => $error, 'username' => $username, 'email' => $email], 422);
            return;
        }

        try {
            $id = User::create($username, $email, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $e) {
            // two people registering the same name at the same moment hit the UNIQUE key
            $this->render('register', ['error' => 'That username or email is already registered.'], 422);
            return;
        }
        $this->logIn($id, $username);
        $this->redirect('/');
    }

    public function loginForm(): void
    {
        $this->render('login');
    }

    public function login(): void
    {
        if (!$this->csrfValid()) {
            $this->error(403, 'Invalid or expired form. Reload the page and try again.');
            return;
        }
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = User::findByUsername($username);

        // Seed users have a NULL hash, so they can never log in.
        if ($user === null || $user['password_hash'] === null
            || !password_verify($password, $user['password_hash'])) {
            $this->render('login', ['error' => 'Wrong username or password.', 'username' => $username], 401);
            return;
        }
        $this->logIn($user['id'], $user['username']);
        $this->redirect('/');
    }

    public function logout(): void
    {
        if (!$this->csrfValid()) {
            $this->error(403, 'Invalid or expired form. Reload the page and try again.');
            return;
        }
        $_SESSION = [];
        session_regenerate_id(true);
        $this->redirect('/');
    }
}
