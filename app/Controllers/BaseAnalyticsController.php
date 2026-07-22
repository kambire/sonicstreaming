<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ListenerSession;
use App\Models\Station;
use App\Models\StationStat;
use App\Services\AnalyticsCollectorService;

abstract class BaseAnalyticsController extends Controller
{
    protected string $base = '/client';

    protected function guard(int $stationId): array
    {
        $station = Station::findWithServer($stationId);
        if (!$station) {
            set_flash('danger', 'Estación no encontrada.');
            redirect($this->base . '/dashboard');
        }
        return $station;
    }

    public function index(Request $request, string $id): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $days = max(1, min(90, $request->int('days', 7)));

        // Intentar sincronizar oyentes activos en tiempo real
        try {
            $collector = new AnalyticsCollectorService();
            $collector->syncActiveListeners($station);
        } catch (\Throwable $e) {
            // Ignorar errores silenciosamente si Shoutcast no está respondiendo
        }

        $kpis       = ListenerSession::kpiSummary($sid, $days);
        $countries  = ListenerSession::countryStats($sid, $days, 10);
        $cities     = ListenerSession::cityStats($sid, $days, 10);
        $devices    = ListenerSession::deviceStats($sid, $days);
        $players    = ListenerSession::playerStats($sid, $days, 6);

        $this->view('analytics/index', [
            'title'     => 'Analíticas de Audiencia · ' . $station['name'],
            'station'   => $station,
            'base'      => $this->base,
            'days'      => $days,
            'kpis'      => $kpis,
            'countries' => $countries,
            'cities'    => $cities,
            'devices'   => $devices,
            'players'   => $players,
        ]);
    }

    public function apiData(Request $request, string $id): void
    {
        $sid = (int) $id;
        $station = $this->guard($sid);
        $days = max(1, min(90, $request->int('days', 7)));

        $markers   = ListenerSession::mapMarkers($sid, $days, 300);
        $kpis      = ListenerSession::kpiSummary($sid, $days);
        $countries = ListenerSession::countryStats($sid, $days, 10);
        $devices   = ListenerSession::deviceStats($sid, $days);
        $history   = StationStat::history($sid, 60);

        // Formatear historia para Chart.js
        $historyLabels = [];
        $historyData   = [];
        foreach ($history as $h) {
            $historyLabels[] = date('H:i', strtotime((string) $h['captured_at']));
            $historyData[]   = (int) $h['current_listeners'];
        }

        json_response([
            'ok'        => true,
            'markers'   => $markers,
            'kpis'      => $kpis,
            'countries' => $countries,
            'devices'   => $devices,
            'history'   => [
                'labels' => $historyLabels,
                'data'   => $historyData,
            ],
        ]);
    }
}
