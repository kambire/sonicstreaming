<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class StationStat extends Model
{
    protected static string $table = 'station_stats';

    /** Ultimo snapshot de una estacion. */
    public static function latest(int $stationId): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM station_stats WHERE station_id = ? ORDER BY captured_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute([$stationId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Serie temporal reciente para graficar (mas antiguo -> mas nuevo).
     * @return array<int,array<string,mixed>>
     */
    public static function history(int $stationId, int $limit = 60): array
    {
        $limit = max(1, min($limit, 500));
        $stmt = self::db()->prepare(
            'SELECT captured_at, current_listeners, is_up
             FROM station_stats WHERE station_id = ?
             ORDER BY captured_at DESC, id DESC LIMIT ' . $limit
        );
        $stmt->execute([$stationId]);
        $rows = $stmt->fetchAll();
        return array_reverse($rows);
    }
}
