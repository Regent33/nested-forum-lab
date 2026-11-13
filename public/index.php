<?php
// Front controller: every request comes through this file.
require __DIR__ . '/../vendor/autoload.php';

// Session cookie: HttpOnly + SameSite, and Secure when the site runs on HTTPS.
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => ($_SERVER['HTTPS'] ?? '') === 'on',
]);
session_start();

$router = require __DIR__ . '/../config/routes.php';
$router->run();
