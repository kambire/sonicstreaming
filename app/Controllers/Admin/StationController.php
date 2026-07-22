<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Station;
use App\Models\StationStat;
use App\Models\User;
use App\Services\Crypto;
use App\Services\ShoutcastService;

final class StationController extends Controller
{
    private ShoutcastService $shoutcast;

    public function __construct()
    {
        $this->shoutcast = new ShoutcastService();
    }

    public function index(Request $request): void
    {
        $this->view('admin/stations/index', [
            'title'    => 'Estaciones',
            'stations' => Station::allWithOwner(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/stations/form', [
            'title'   => 'Nueva estacion',
            'station' => null,
            'clients' => User::clients(),
            'servers' => Server::where(['status' => 'active'], 'name ASC'),
            'plans'   => Plan::all('name ASC'),
        ]);
    }

    public function store(Request $request): void
    {
        $name    = $request->str('name');
        $userId  = $request->int('user_id', 0);
        $serverId = $request->int('server_id', 0);

        if ($name === '' || $userId <= 0 || $serverId <= 0) {
            set_flash('danger', 'Nombre, cliente y servidor son obligatorios.');
            $this->flashOld($request->all());
            redirect('admin/stations/create');
        }

        $port = Server::nextFreePort($serverId);
        if ($port === null) {
            set_flash('danger', 'No hay puertos libres en el servidor seleccionado.');
            redirect('admin/stations/create');
        }

        [$maxListeners, $maxBitrate] = $this->applyPlanLimits($request);

        $sourcePass = $request->str('source_password') ?: random_password(10);
        $adminPass  = $request->str('admin_password') ?: random_password(12);

        $type = $request->str('type', 'live') === 'relay' ? 'relay' : 'live';

        $id = Station::create([
            'user_id'         => $userId,
            'server_id'       => $serverId,
            'plan_id'         => $request->int('plan_id', 0) ?: null,
            'name'            => $name,
            'port'            => $port,
            'dj_port'         => $port + 10000,
            'source_password' => $sourcePass,
            'admin_password'  => Crypto::encrypt($adminPass),
            'max_listeners'   => $maxListeners,
            'max_bitrate'     => $maxBitrate,
            'type'            => $type,
            'relay_url'       => $type === 'relay' ? $request->str('relay_url') : null,
            'genre'           => $request->str('genre'),
            'autodj_enabled'  => $request->input('autodj_enabled') ? 1 : 0,
            'status'          => 'stopped',
        ]);

        ActivityLog::record('station_create', 'Station #' . $id . ' puerto ' . $port);
        $this->clearOld();
        set_flash('success', "Estacion creada en el puerto {$port}.");
        redirect('admin/stations/' . $id);
    }

    public function show(Request $request, string $id): void
    {
        $station = Station::findWithServer((int) $id);
        if (!$station) {
            set_flash('danger', 'Estacion no encontrada.');
            redirect('admin/stations');
        }
        $owner = User::find((int) $station['user_id']);
        $this->view('admin/stations/show', [
            'title'    => $station['name'],
            'station'  => $station,
            'owner'    => $owner,
            'latest'   => StationStat::latest((int) $id),
            'adminPass'=> Crypto::decrypt((string) $station['admin_password']),
            'base'     => 'admin',
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        $station = Station::find((int) $id);
        if (!$station) {
            redirect('admin/stations');
        }
        $this->view('admin/stations/form', [
            'title'   => 'Editar estacion',
            'station' => $station,
            'clients' => User::clients(),
            'servers' => Server::all('name ASC'),
            'plans'   => Plan::all('name ASC'),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $station = Station::find((int) $id);
        if (!$station) {
            redirect('admin/stations');
        }

        [$maxListeners, $maxBitrate] = $this->applyPlanLimits($request);
        $type = $request->str('type', 'live') === 'relay' ? 'relay' : 'live';

        $data = [
            'name'           => $request->str('name', $station['name']),
            'plan_id'        => $request->int('plan_id', 0) ?: null,
            'max_listeners'  => $maxListeners,
            'max_bitrate'    => $maxBitrate,
            'type'           => $type,
            'relay_url'      => $type === 'relay' ? $request->str('relay_url') : null,
            'genre'          => $request->str('genre'),
            'autodj_enabled' => $request->input('autodj_enabled') ? 1 : 0,
        ];

        // Cambio opcional de contrasenas
        if ($request->str('source_password') !== '') {
            $data['source_password'] = $request->str('source_password');
        }
        if ($request->str('admin_password') !== '') {
            $data['admin_password'] = Crypto::encrypt($request->str('admin_password'));
        }

        Station::update((int) $id, $data);
        ActivityLog::record('station_update', 'Station #' . $id);
        set_flash('success', 'Estacion actualizada. Reiniciala para aplicar cambios de streaming.');
        redirect('admin/stations/' . $id);
    }

    public function destroy(Request $request, string $id): void
    {
        $station = Station::findWithServer((int) $id);
        if ($station) {
            $this->shoutcast->stop($station); // best-effort
            @unlink($this->shoutcast->configPath((int) $id));
        }
        Station::delete((int) $id);
        ActivityLog::record('station_delete', 'Station #' . $id);
        set_flash('success', 'Estacion eliminada.');
        redirect('admin/stations');
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

    // -----------------------------------------------------------------

    private function action(int $id, string $action): void
    {
        $station = Station::findWithServer($id);
        if (!$station) {
            set_flash('danger', 'Estacion no encontrada.');
            redirect('admin/stations');
        }
        if ($station['status'] === 'suspended') {
            set_flash('warning', 'La estacion esta suspendida (revisa la facturacion).');
            redirect('admin/stations/' . $id);
        }

        $result = match ($action) {
            'start'   => $this->shoutcast->start($station),
            'stop'    => $this->shoutcast->stop($station),
            'restart' => $this->shoutcast->restart($station),
            default   => ['ok' => false, 'message' => 'Accion desconocida'],
        };

        ActivityLog::record('station_' . $action, 'Station #' . $id);
        set_flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect('admin/stations/' . $id);
    }

    /**
     * Devuelve [maxListeners, maxBitrate] respetando el limite del plan si aplica.
     * @return array{0:int,1:int}
     */
    private function applyPlanLimits(Request $request): array
    {
        $maxListeners = max(1, $request->int('max_listeners', 100));
        $maxBitrate   = max(8, $request->int('max_bitrate', 128));

        $planId = $request->int('plan_id', 0);
        if ($planId > 0) {
            $plan = Plan::find($planId);
            if ($plan) {
                $maxListeners = min($maxListeners, (int) $plan['max_listeners']);
                $maxBitrate   = min($maxBitrate, (int) $plan['max_bitrate']);
            }
        }
        return [$maxListeners, $maxBitrate];
    }
}
