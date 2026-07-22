<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Station;
use App\Models\StationStat;
use App\Services\Crypto;
use App\Services\ShoutcastService;

final class StationController extends Controller
{
    private ShoutcastService $shoutcast;

    public function __construct()
    {
        $this->shoutcast = new ShoutcastService();
    }

    /** Devuelve la estacion (con servidor) si pertenece al cliente actual. */
    private function own(int $id): ?array
    {
        $station = Station::findWithServer($id);
        if (!$station || (int) $station['user_id'] !== (int) Auth::id()) {
            return null;
        }
        return $station;
    }

    private function mustOwn(int $id): array
    {
        $station = $this->own($id);
        if (!$station) {
            http_response_code(404);
            echo \App\Core\View::render('errors/404', [], 'layouts/blank');
            exit;
        }
        return $station;
    }

    public function show(Request $request, string $id): void
    {
        $station = $this->mustOwn((int) $id);
        $this->view('client/stations/show', [
            'title'     => $station['name'],
            'station'   => $station,
            'latest'    => StationStat::latest((int) $id),
            'adminPass' => Crypto::decrypt((string) $station['admin_password']),
        ]);
    }

    public function start(Request $request, string $id): void
    {
        $this->action((int) $id, 'start');
    }

    public function stop(Request $request, string $id): void
    {
        $this->action((int) $id, 'stop');
    }

    public function restart(Request $request, string $id): void
    {
        $this->action((int) $id, 'restart');
    }

    public function updateSettings(Request $request, string $id): void
    {
        $station = Station::findWithServer((int) $id);
        if (!$station) {
            http_response_code(404);
            return;
        }

        if (Auth::role() === 'client' && (int) $station['user_id'] !== (int) Auth::id()) {
            http_response_code(403);
            return;
        }

        $type = $request->str('type', $station['type']);
        if (!in_array($type, ['live', 'relay'], true)) {
            $type = 'live';
        }

        $data = [
            'name'      => $request->str('name', $station['name']),
            'genre'     => $request->str('genre'),
            'type'      => $type,
            'relay_url' => $request->str('relay_url'),
        ];
        if ($request->str('source_password') !== '') {
            $data['source_password'] = $request->str('source_password');
        }

        Station::update((int) $id, $data);

        // Regenerar configuraciones de Shoutcast y AutoDj
        $updatedStation = Station::findWithServer((int) $id);
        if ($updatedStation) {
            $this->shoutcast->generateConfig($updatedStation);
            $autodj = new \App\Services\AutoDjService();
            $autodj->reloadIfRunning($updatedStation);
        }

        ActivityLog::record('station_settings', 'Station #' . $id);
        set_flash('success', 'Configuración de emisora y Re-transmisión Relay guardada.');

        $role = Auth::role();
        $base = ($role === 'admin') ? 'admin' : (($role === 'reseller') ? 'reseller' : 'client');
        redirect($base . '/stations/' . $id);
    }

    private function action(int $id, string $action): void
    {
        $station = $this->mustOwn($id);
        if ($station['status'] === 'suspended') {
            set_flash('warning', 'Tu estacion esta suspendida. Revisa tus facturas pendientes.');
            redirect('client/stations/' . $id);
        }
        $result = match ($action) {
            'start'   => $this->shoutcast->start($station),
            'stop'    => $this->shoutcast->stop($station),
            'restart' => $this->shoutcast->restart($station),
            default   => ['ok' => false, 'message' => 'Accion desconocida'],
        };
        ActivityLog::record('station_' . $action, 'Station #' . $id . ' (cliente)');
        set_flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect('client/stations/' . $id);
    }
}
