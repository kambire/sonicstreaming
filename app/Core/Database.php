<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Conexion PDO (singleton) a MariaDB/MySQL.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $name = (string) env('DB_NAME', 'sonicstreaming');
        $user = (string) env('DB_USER', 'root');
        $pass = (string) env('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            if (env('APP_DEBUG', false) === true) {
                exit('Error de conexion a la base de datos: ' . $e->getMessage());
            }
            exit('Error de conexion a la base de datos.');
        }

        return self::$instance;
    }
}
