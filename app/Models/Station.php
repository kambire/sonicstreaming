<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Station extends Model
{
    protected static string $table = 'stations';

    /**
     * Lista de estaciones con datos del dueno y servidor (JOIN) para tablas.
     * @return array<int,array<string,mixed>>
     */
    public static function allWithOwner(): array
    {
        $sql = 'SELECT s.*, u.name AS owner_name, u.email AS owner_email, sv.name AS server_name, sv.hostname
                FROM stations s
                JOIN users u   ON u.id = s.user_id
                JOIN servers sv ON sv.id = s.server_id
                ORDER BY s.id DESC';
        return self::db()->query($sql)->fetchAll();
    }

    /** Estaciones de un cliente concreto. */
    public static function forUser(int $userId): array
    {
        return self::where(['user_id' => $userId], 'name ASC');
    }

    /** Estaciones cuyos duenos pertenecen a un reseller. */
    public static function forReseller(int $resellerId): array
    {
        $sql = 'SELECT s.*, u.name AS owner_name, sv.hostname
                FROM stations s
                JOIN users u ON u.id = s.user_id
                JOIN servers sv ON sv.id = s.server_id
                WHERE u.reseller_id = ?
                ORDER BY s.id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$resellerId]);
        return $stmt->fetchAll();
    }

    /** Estacion con datos del servidor. */
    public static function findWithServer(int $id): ?array
    {
        $sql = 'SELECT s.*, sv.hostname, sv.driver, sv.name AS server_name
                FROM stations s
                JOIN servers sv ON sv.id = s.server_id
                WHERE s.id = ? LIMIT 1';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<int,array<string,mixed>> Estaciones activas (no suspendidas) con servidor. */
    public static function activeWithServer(): array
    {
        $sql = 'SELECT s.*, sv.hostname, sv.driver
                FROM stations s
                JOIN servers sv ON sv.id = s.server_id
                WHERE s.status <> "suspended"';
        return self::db()->query($sql)->fetchAll();
    }
}
