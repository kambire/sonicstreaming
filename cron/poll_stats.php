<?php

declare(strict_types=1);

/**
 * Poller de estadisticas. Ejecutar cada 1-2 minutos por cron.
 *
 *   php cron/poll_stats.php
 *
 * Consulta cada estacion no suspendida, guarda un snapshot en station_stats
 * y actualiza el estado up/down.
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Models\Station;
use App\Models\StationStat;
use App\Services\ShoutcastService;

$service = new ShoutcastService();
$stations = Station::activeWithServer();

$saved = 0;
foreach ($stations as $station) {
    $stats = $service->fetchStats($station);

    StationStat::create([
        'station_id'        => (int) $station['id'],
        'current_listeners' => $stats['current_listeners'],
        'peak_listeners'    => $stats['peak_listeners'],
        'unique_listeners'  => $stats['unique_listeners'],
        'song_title'        => mb_substr($stats['song_title'], 0, 255),
        'bitrate'           => $stats['bitrate'],
        'is_up'             => $stats['is_up'] ? 1 : 0,
    ]);
    $saved++;
}

echo '[' . date('Y-m-d H:i:s') . "] Snapshots guardados: {$saved}\n";

// Limpieza: conservar solo los ultimos 3 dias de historico.
try {
    \App\Core\Database::connection()->exec(
        "DELETE FROM station_stats WHERE captured_at < (NOW() - INTERVAL 3 DAY)"
    );
} catch (\Throwable $e) {
    // silencioso
}
