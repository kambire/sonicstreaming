<?php
/**
 * Bootstrap de la aplicacion.
 * - Autoloader PSR-4 propio (namespace App\ -> carpeta app/)
 * - Carga de variables de entorno desde .env
 * - Helpers globales
 * No requiere Composer, funciona directo en XAMPP.
 */

declare(strict_types=1);

define('BASE_PATH', __DIR__);

// ---------------------------------------------------------------------------
// Autoloader PSR-4:  App\Core\Router -> app/Core/Router.php
// ---------------------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Helpers globales (funciones) — deben cargarse antes de usar env()
require BASE_PATH . '/app/Core/helpers.php';

// ---------------------------------------------------------------------------
// Cargar .env
// ---------------------------------------------------------------------------
\App\Core\Env::load(BASE_PATH . '/.env');

date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

if (env('APP_DEBUG', false) === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
