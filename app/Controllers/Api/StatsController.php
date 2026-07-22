<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Station;
use App\Models\StationStat;
use App\Models\User;
use App\Services\ShoutcastService;

final class StatsController extends Controller
{
    public function station(Request $request, string $id): void
    {
        $station = $this->authorized((int) $id);
        if (!$station) {
            $this->json(['error' => 'forbidden'], 403);
            return;
        }
        $stats = (new ShoutcastService())->fetchStats($station);
        try {
            (new \App\Services\AnalyticsCollectorService())->syncActiveListeners($station);
        } catch (\Throwable $e) {}
        $this->json($stats);
    }

    public function history(Request $request, string $id): void
    {
        $station = $this->authorized((int) $id);
        if (!$station) {
            $this->json(['error' => 'forbidden'], 403);
            return;
        }
        $rows = StationStat::history((int) $id, 60);
        $labels = [];
        $data = [];
        foreach ($rows as $r) {
            $labels[] = substr((string) $r['captured_at'], 11, 5); // HH:MM
            $data[] = (int) $r['current_listeners'];
        }
        $this->json(['labels' => $labels, 'data' => $data]);
    }

    /**
     * Devuelve la estacion (con datos de servidor) si el usuario actual puede verla.
     * @return array<string,mixed>|null
     */
    private function authorized(int $id): ?array
    {
        $station = Station::findWithServer($id);
        if (!$station) {
            return null;
        }
        $role = Auth::role();
        if ($role === 'admin') {
            return $station;
        }
        if ($role === 'client') {
            return (int) $station['user_id'] === Auth::id() ? $station : null;
        }
        if ($role === 'reseller') {
            $owner = User::find((int) $station['user_id']);
            return $owner && (int) ($owner['reseller_id'] ?? 0) === Auth::id() ? $station : null;
        }
        return null;
    }
}
