<?php

declare(strict_types=1);

/**
 * Restauracion tras reinicio del servidor.
 * Vuelve a iniciar las estaciones (y el AutoDJ) que estaban "en linea"
 * segun la base de datos. Se ejecuta con @reboot desde cron.
 *
 *   php cron/boot_restore.php
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Models\Station;
use App\Services\AutoDjService;
use App\Services\ShoutcastService;

$shoutcast = new ShoutcastService();
$autodj    = new AutoDjService();

$started = 0;
$adStarted = 0;

foreach (Station::activeWithServer() as $station) {
    if (($station['status'] ?? '') === 'running') {
        $r = $shoutcast->start($station);
        if ($r['ok']) {
            $started++;
        }
    }
    if ((int) ($station['autodj_enabled'] ?? 0) === 1 && ($station['autodj_status'] ?? '') === 'running') {
        $r = $autodj->start($station);
        if ($r['ok']) {
            $adStarted++;
        }
    }
}

echo '[' . date('Y-m-d H:i:s') . "] Boot restore: {$started} estacion(es), {$adStarted} AutoDJ iniciados\n";
