<?php

declare(strict_types=1);

/**
 * Proceso diario de facturacion. Ejecutar una vez al dia por cron.
 *
 *   php cron/billing_run.php
 *
 * Marca las facturas vencidas como 'overdue' y suspende automaticamente
 * las estaciones asociadas (detiene el proceso Shoutcast).
 */

require dirname(__DIR__) . '/bootstrap.php';

use App\Services\BillingService;

$result = (new BillingService())->runOverdue();

echo '[' . date('Y-m-d H:i:s') . '] Facturacion: '
    . $result['overdue'] . ' vencidas, '
    . $result['suspended'] . " estacion(es) suspendida(s)\n";
