<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class MediaTrack extends Model
{
    protected static string $table = 'media_tracks';

    /** @return array<int,array<string,mixed>> */
    public static function forStation(int $stationId): array
    {
        return self::where(['station_id' => $stationId], 'created_at DESC');
    }

    public static function countForStation(int $stationId): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM media_tracks WHERE station_id = ?');
        $stmt->execute([$stationId]);
        return (int) $stmt->fetchColumn();
    }

    /** Uso total de disco de la estacion en bytes. */
    public static function diskUsage(int $stationId): int
    {
        $stmt = self::db()->prepare('SELECT COALESCE(SUM(filesize),0) FROM media_tracks WHERE station_id = ?');
        $stmt->execute([$stationId]);
        return (int) $stmt->fetchColumn();
    }
}
