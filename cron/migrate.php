<?php

declare(strict_types=1);

/**
 * Instalador / migrador por linea de comandos.
 *
 *   php cron/migrate.php
 *
 * - Crea la base de datos si no existe.
 * - Ejecuta todas las migraciones .sql en database/migrations en orden.
 * - Siembra admin inicial, servidor local y plan demo (idempotente).
 */

require dirname(__DIR__) . '/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit('Este script solo se ejecuta por linea de comandos.');
}

$host = (string) env('DB_HOST', '127.0.0.1');
$port = (string) env('DB_PORT', '3306');
$name = (string) env('DB_NAME', 'sonicstreaming');
$user = (string) env('DB_USER', 'root');
$pass = (string) env('DB_PASS', '');

echo "== Migrador SonicStreaming ==\n";

// 1) Conectar sin base de datos y crearla
try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    exit("No se pudo conectar a MySQL: {$e->getMessage()}\n");
}

// Crear la BD si tenemos privilegios; si el usuario no puede crear bases
// (tipico usuario de app con permisos solo sobre su BD), continuamos.
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    echo "[--] No se pudo crear la BD (se asume que ya existe): {$e->getMessage()}\n";
}
try {
    $pdo->exec("USE `{$name}`");
} catch (PDOException $e) {
    exit("No hay acceso a la base de datos '{$name}': {$e->getMessage()}\n");
}
echo "[ok] Base de datos '{$name}' lista.\n";

// 2) Ejecutar migraciones
$dir = BASE_PATH . '/database/migrations';
$files = glob($dir . '/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        continue;
    }
    try {
        $pdo->exec($sql);
        echo '[ok] Migracion aplicada: ' . basename($file) . "\n";
    } catch (PDOException $e) {
        echo '[!!] Error en ' . basename($file) . ': ' . $e->getMessage() . "\n";
    }
}

// 3) Seed idempotente. Credenciales del admin configurables por entorno
//    (el instalador de produccion genera una contrasena aleatoria).
$adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@sonic.local';
$adminPass  = getenv('ADMIN_PASSWORD') ?: 'admin123';

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$adminEmail]);
if (!$stmt->fetch()) {
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO users (name,email,password_hash,role,status) VALUES (?,?,?,?,?)')
        ->execute(['Administrador', $adminEmail, $hash, 'admin', 'active']);
    echo "[ok] Admin creado -> {$adminEmail} / {$adminPass}\n";
} else {
    echo "[--] Admin ya existia ({$adminEmail}).\n";
}

// Servidor local por defecto
$driver = (string) env('SHOUTCAST_DRIVER', 'mock');
$hostSc = (string) env('SHOUTCAST_HOST', '127.0.0.1');
if ((int) $pdo->query('SELECT COUNT(*) FROM servers')->fetchColumn() === 0) {
    $pdo->prepare('INSERT INTO servers (name,hostname,driver,port_range_start,port_range_end,max_streams,status) VALUES (?,?,?,?,?,?,?)')
        ->execute(['Servidor Local', $hostSc, $driver, 8000, 8100, 50, 'active']);
    echo "[ok] Servidor local creado (driver={$driver}).\n";
}

// Plan demo
if ((int) $pdo->query('SELECT COUNT(*) FROM plans')->fetchColumn() === 0) {
    $pdo->prepare('INSERT INTO plans (name,max_bitrate,max_listeners,disk_quota_mb,price,billing_cycle) VALUES (?,?,?,?,?,?)')
        ->execute(['Plan Basico 128k', 128, 100, 500, 9.99, 'monthly']);
    $pdo->prepare('INSERT INTO plans (name,max_bitrate,max_listeners,disk_quota_mb,price,billing_cycle) VALUES (?,?,?,?,?,?)')
        ->execute(['Plan Pro 320k', 320, 500, 2048, 24.99, 'monthly']);
    echo "[ok] Planes demo creados.\n";
}

echo "== Listo. Entra en el panel y usa {$adminEmail} / {$adminPass} ==\n";
