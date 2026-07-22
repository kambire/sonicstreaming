<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;

// Sesion segura. El flag "secure" se activa solo bajo HTTPS para no romper
// el modo desarrollo por HTTP (XAMPP).
$isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) == 443)
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => $isHttps,
]);
session_start();

require dirname(__DIR__) . '/bootstrap.php';

$router = new Router();
(require BASE_PATH . '/config/routes.php')($router);

try {
    $router->dispatch(new Request());
} catch (\Throwable $e) {
    http_response_code(500);
    if (env('APP_DEBUG', false) === true) {
        echo '<h1>Error</h1><pre>' . e($e->getMessage()) . "\n\n" . e($e->getTraceAsString()) . '</pre>';
    } else {
        echo \App\Core\View::render('errors/500', [], 'layouts/blank');
    }
}
