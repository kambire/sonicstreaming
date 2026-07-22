<?php

declare(strict_types=1);

use App\Core\Env;
use App\Core\Auth;

/**
 * Lee una variable de entorno con casteo de booleanos.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = Env::get($key, $default);
    if (is_string($value)) {
        return match (strtolower($value)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $value,
        };
    }
    return $value;
}

/**
 * Escapa texto para salida HTML segura.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * URL base de la aplicacion (ej: /sonicstreaming/public).
 */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    }
    return $base;
}

/**
 * Construye una URL interna a partir de una ruta.
 */
function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/**
 * URL a un asset dentro de public/assets.
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Redireccion HTTP a una ruta interna.
 */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Usuario autenticado actual (array) o null.
 */
function auth(): ?array
{
    return Auth::user();
}

/**
 * Devuelve y limpia un mensaje flash de sesion.
 */
function flash(string $key): ?array
{
    if (!empty($_SESSION['_flash'][$key])) {
        $msg = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $msg;
    }
    return null;
}

/**
 * Define un mensaje flash (tipo: success|danger|warning|info).
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['_flash']['message'] = ['type' => $type, 'text' => $message];
}

/**
 * Genera una contrasena aleatoria segura (para streams).
 */
function random_password(int $length = 12): string
{
    $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, $max)];
    }
    return $out;
}

/**
 * Formatea dinero.
 */
function money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

/**
 * Formatea bytes a un tamano legible.
 */
function human_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $val = (float) $bytes;
    while ($val >= 1024 && $i < count($units) - 1) {
        $val /= 1024;
        $i++;
    }
    return round($val, $i === 0 ? 0 : 1) . ' ' . $units[$i];
}

/**
 * old() para repoblar formularios tras validacion fallida.
 */
function old(string $key, string $default = ''): string
{
    return isset($_SESSION['_old'][$key]) ? (string) $_SESSION['_old'][$key] : $default;
}

/**
 * Ruta actual (sin el prefijo base), util para marcar el menu activo.
 */
function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_url();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return '/' . trim($uri, '/');
}

/**
 * Devuelve 'active' si la ruta actual empieza por $prefix.
 */
function nav_active(string $prefix): string
{
    return str_starts_with(request_path(), $prefix) ? 'active' : '';
}

/**
 * Etiqueta de estado (badge) para una estacion.
 */
function status_badge(string $status): string
{
    return match ($status) {
        'running'   => '<span class="badge bg-success">En linea</span>',
        'stopped'   => '<span class="badge bg-secondary">Detenida</span>',
        'suspended' => '<span class="badge bg-danger">Suspendida</span>',
        'active'    => '<span class="badge bg-success">Activo</span>',
        'paid'      => '<span class="badge bg-success">Pagada</span>',
        'pending'   => '<span class="badge bg-warning text-dark">Pendiente</span>',
        'overdue'   => '<span class="badge bg-danger">Vencida</span>',
        default     => '<span class="badge bg-secondary">' . e($status) . '</span>',
    };
}
