<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Station;

/**
 * Reglas de facturacion: marcar vencidas y suspender / reactivar estaciones.
 */
final class BillingService
{
    /**
     * Procesa las facturas vencidas: las marca como 'overdue' y suspende
     * la(s) estacion(es) asociada(s). Devuelve un resumen.
     *
     * @return array{overdue:int,suspended:int}
     */
    public function runOverdue(?string $today = null): array
    {
        $today = $today ?? date('Y-m-d');
        $rows = Invoice::overdueUnpaid($today);

        $overdue = 0;
        $suspended = 0;
        $shoutcast = new ShoutcastService();

        foreach ($rows as $inv) {
            if ($inv['status'] === 'pending') {
                Invoice::update((int) $inv['id'], ['status' => 'overdue']);
                $overdue++;
            }

            // Estaciones a suspender
            $stations = [];
            if (!empty($inv['station_id'])) {
                $s = Station::findWithServer((int) $inv['station_id']);
                if ($s) {
                    $stations[] = $s;
                }
            } else {
                // Todas las estaciones del usuario
                foreach (Station::forUser((int) $inv['user_id']) as $s) {
                    $full = Station::findWithServer((int) $s['id']);
                    if ($full) {
                        $stations[] = $full;
                    }
                }
            }

            foreach ($stations as $s) {
                if ($s['status'] !== 'suspended') {
                    $shoutcast->stop($s); // detener proceso (best-effort)
                    Station::update((int) $s['id'], ['status' => 'suspended']);
                    $suspended++;
                }
            }
        }

        return ['overdue' => $overdue, 'suspended' => $suspended];
    }

    /**
     * Reactiva las estaciones suspendidas de un usuario si ya no tiene
     * facturas vencidas. Las deja en estado 'stopped' (listas para iniciar).
     */
    public function reactivateUser(int $userId): int
    {
        $stillOverdue = Invoice::where(['user_id' => $userId, 'status' => 'overdue']);
        if ($stillOverdue) {
            return 0; // aun tiene deudas
        }
        $count = 0;
        foreach (Station::where(['user_id' => $userId, 'status' => 'suspended']) as $s) {
            Station::update((int) $s['id'], ['status' => 'stopped']);
            $count++;
        }
        return $count;
    }
}
