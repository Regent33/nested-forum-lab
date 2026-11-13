<?php
use App\Controllers\AuthController;
use App\Controllers\CommentController;
use App\Controllers\DiscussionController;
use Bramus\Router\Router;

$router = new Router();
$discussions = new DiscussionController();
$auth = new AuthController();

$router->get('/', [$discussions, 'index']);
// ([^/]+) instead of (\d+) so a malformed ID reaches the controller and gets a 400
$router->get('/discussions/([^/]+)', [$discussions, 'show']);
$router->post('/discussions/([^/]+)/comments', [new CommentController(), 'store']);

$router->get('/register', [$auth, 'registerForm']);
$router->post('/register', [$auth, 'register']);
$router->get('/login', [$auth, 'loginForm']);
$router->post('/login', [$auth, 'login']);
$router->post('/logout', [$auth, 'logout']);

$router->set404(function () use ($discussions) {
    $discussions->notFound();
});

return $router;
