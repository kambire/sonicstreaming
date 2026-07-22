<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Server extends Model
{
    protected static string $table = 'servers';

    /**
     * Devuelve el siguiente puerto par libre dentro del rango del servidor.
     * Shoutcast usa port y port+1, por eso avanzamos de 2 en 2.
     */
    public static function nextFreePort(int $serverId): ?int
    {
        $server = self::find($serverId);
        if (!$server) {
            return null;
        }
        $start = (int) $server['port_range_start'];
        $end   = (int) $server['port_range_end'];

        $stmt = self::db()->prepare('SELECT port FROM stations WHERE server_id = ?');
        $stmt->execute([$serverId]);
        $used = array_map('intval', array_column($stmt->fetchAll(), 'port'));
        $used = array_flip($used);

        for ($p = $start; $p <= $end; $p += 2) {
            if (!isset($used[$p])) {
                return $p;
            }
        }
        return null;
    }

    public static function isPortAvailable(int $serverId, int $port, int $ignoreStationId = 0): bool
    {
        $stmt = self::db()->prepare('SELECT id FROM stations WHERE server_id = ? AND port = ? AND id <> ? LIMIT 1');
        $stmt->execute([$serverId, $port, $ignoreStationId]);
        return !$stmt->fetch();
    }

    public static function activeCount(): int
    {
        return self::count("status = 'active'");
    }
}
