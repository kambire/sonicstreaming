<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Station;
use App\Services\AutoDjService;
use App\Services\ShoutcastService;

final class RelayController extends Controller
{
    private ShoutcastService $shoutcast;
    private AutoDjService $autodj;

    public function __construct()
    {
        $this->shoutcast = new ShoutcastService();
        $this->autodj    = new AutoDjService();
    }

    private function getStation(int $id): array
    {
        $station = Station::findWithServer($id);
        if (!$station) {
            http_response_code(404);
            echo \App\Core\View::render('errors/404', [], 'layouts/blank');
            exit;
        }

        if (Auth::role() === 'client' && (int) $station['user_id'] !== (int) Auth::id()) {
            http_response_code(403);
            echo \App\Core\View::render('errors/403', [], 'layouts/blank');
            exit;
        }

        return $station;
    }

    public function index(Request $request, string $id): void
    {
        $station = $this->getStation((int) $id);
        $role = Auth::role();
        $baseRole = ($role === 'admin') ? 'admin' : (($role === 'reseller') ? 'reseller' : 'client');

        $this->view('relay/index', [
            'title'    => 'Configuración & Tutorial de Re-transmisión Relay - ' . $station['name'],
            'station'  => $station,
            'baseRole' => $baseRole,
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $station   = $this->getStation((int) $id);
        $relayUrl  = trim($request->str('relay_url'));
        $relayMode = $request->str('relay_mode', 'fulltime');
        
        if (!in_array($relayMode, ['fulltime', 'scheduled', 'exclusive', 'disabled'], true)) {
            $relayMode = 'fulltime';
        }

        $startHour = trim($request->str('relay_start_hour'));
        $endHour   = trim($request->str('relay_end_hour'));

        $data = [
            'relay_url'        => $relayUrl,
            'relay_mode'       => $relayMode,
            'relay_start_hour' => $startHour !== '' ? $startHour : null,
            'relay_end_hour'   => $endHour !== '' ? $endHour : null,
        ];

        Station::update((int) $id, $data);

        // Regenerar servicios de Shoutcast y Liquidsoap
        $updated = Station::findWithServer((int) $id);
        if ($updated) {
            $this->shoutcast->generateConfig($updated);
            $this->autodj->generateScript($updated);
            $this->autodj->reloadIfRunning($updated);
        }

        ActivityLog::record('relay_update', 'Station #' . $id . ' Relay Mode: ' . $relayMode);
        set_flash('success', 'Configuración y horarios de Re-transmisión Relay actualizados correctamente.');

        $role = Auth::role();
        $baseRole = ($role === 'admin') ? 'admin' : (($role === 'reseller') ? 'reseller' : 'client');
        redirect($baseRole . '/stations/' . $id . '/relay');
    }
}
