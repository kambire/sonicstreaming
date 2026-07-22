<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ListenerSession extends Model
{
    protected static string $table = 'listener_sessions';

    /** @return array<int,array<string,mixed>> */
    public static function activeForStation(int $stationId): array
    {
        $stmt = self::db()->prepare(
            'SELECT * FROM listener_sessions 
             WHERE station_id = ? AND disconnected_at IS NULL 
             ORDER BY connected_at DESC'
        );
        $stmt->execute([$stationId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed> */
    public static function kpiSummary(int $stationId, int $days = 7): array
    {
        $sql = 'SELECT 
                    COUNT(DISTINCT listener_ip) AS unique_listeners,
                    COUNT(id) AS total_sessions,
                    COALESCE(AVG(duration_seconds), 0) AS avg_duration_sec,
                    COALESCE(SUM(bytes_sent), 0) AS total_bytes
                FROM listener_sessions
                WHERE station_id = ? AND connected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$stationId, $days]);
        $res = $stmt->fetch() ?: [];

        // Obtener pico de oyentes concurrentes en ese periodo desde station_stats
        $stmtPeak = self::db()->prepare(
            'SELECT COALESCE(MAX(current_listeners), 0) AS peak 
             FROM station_stats 
             WHERE station_id = ? AND captured_at >= DATE_SUB(NOW(), INTERVAL ? DAY)'
        );
        $stmtPeak->execute([$stationId, $days]);
        $peak = (int) ($stmtPeak->fetchColumn() ?: 0);

        return [
            'unique_listeners' => (int) ($res['unique_listeners'] ?? 0),
            'total_sessions'   => (int) ($res['total_sessions'] ?? 0),
            'avg_duration_sec' => (int) round((float) ($res['avg_duration_sec'] ?? 0)),
            'total_bytes'      => (int) ($res['total_bytes'] ?? 0),
            'peak_listeners'   => $peak,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function countryStats(int $stationId, int $days = 7, int $limit = 10): array
    {
        $sql = 'SELECT 
                    COALESCE(country, "Desconocido") AS country,
                    COALESCE(country_code, "XX") AS country_code,
                    COUNT(id) AS sessions_count,
                    COUNT(DISTINCT listener_ip) AS unique_count,
                    COALESCE(SUM(duration_seconds), 0) AS total_duration_sec
                FROM listener_sessions
                WHERE station_id = ? AND connected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY country, country_code
                ORDER BY sessions_count DESC
                LIMIT ' . (int) $limit;
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$stationId, $days]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public static function cityStats(int $stationId, int $days = 7, int $limit = 10): array
    {
        $sql = 'SELECT 
                    COALESCE(city, "Desconocida") AS city,
                    COALESCE(country, "Desconocido") AS country,
                    COALESCE(country_code, "XX") AS country_code,
                    COUNT(id) AS sessions_count
                FROM listener_sessions
                WHERE station_id = ? AND connected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY city, country, country_code
                ORDER BY sessions_count DESC
                LIMIT ' . (int) $limit;
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$stationId, $days]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public static function deviceStats(int $stationId, int $days = 7): array
    {
        $sql = 'SELECT device_type, COUNT(id) AS count
                FROM listener_sessions
                WHERE station_id = ? AND connected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY device_type
                ORDER BY count DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$stationId, $days]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public static function playerStats(int $stationId, int $days = 7, int $limit = 8): array
    {
        $sql = 'SELECT COALESCE(player_name, "Otro / Desconocido") AS player_name, COUNT(id) AS count
                FROM listener_sessions
                WHERE station_id = ? AND connected_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY player_name
                ORDER BY count DESC
                LIMIT ' . (int) $limit;
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$stationId, $days]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public static function mapMarkers(int $stationId, int $days = 7, int $limit = 300): array
    {
        $sql = 'SELECT 
                    id, listener_ip, country, country_code, city, latitude, longitude,
                    user_agent, device_type, player_name, connected_at, duration_seconds,
                    (disconnected_at IS NULL) AS is_live
                FROM listener_sessions
                WHERE station_id = ? 
                  AND latitude IS NOT NULL 
                  AND longitude IS NOT NULL
                  AND (connected_at >= DATE_SUB(NOW(), INTERVAL ? DAY) OR disconnected_at IS NULL)
                ORDER BY is_live DESC, connected_at DESC
                LIMIT ' . (int) $limit;
        $stmt = self::db()->prepare($sql);
        $stmt->execute([$stationId, $days]);
        return $stmt->fetchAll();
    }
}
