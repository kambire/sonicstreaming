<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
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
        $this->view('reseller/stations/index', [
            'title'    => 'Estaciones de mis clientes',
            'stations' => Station::forReseller((int) Auth::id()),
        ]);
    }

    /** Estacion cuyo dueno pertenece a este reseller. */
    private function scoped(int $id): array
    {
        $station = Station::findWithServer($id);
        $owner = $station ? User::find((int) $station['user_id']) : null;
        if (!$station || !$owner || (int) ($owner['reseller_id'] ?? 0) !== (int) Auth::id()) {
            http_response_code(404);
            echo \App\Core\View::render('errors/404', [], 'layouts/blank');
            exit;
        }
        $station['owner_name'] = $owner['name'];
        return $station;
    }

    public function show(Request $request, string $id): void
    {
        $station = $this->scoped((int) $id);
        $this->view('reseller/stations/show', [
            'title'     => $station['name'],
            'station'   => $station,
            'owner'     => ['name' => $station['owner_name']],
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

    private function action(int $id, string $action): void
    {
        $station = $this->scoped($id);
        if ($station['status'] === 'suspended') {
            set_flash('warning', 'La estacion esta suspendida.');
            redirect('reseller/stations/' . $id);
        }
        $result = match ($action) {
            'start'   => $this->shoutcast->start($station),
            'stop'    => $this->shoutcast->stop($station),
            'restart' => $this->shoutcast->restart($station),
            default   => ['ok' => false, 'message' => 'Accion desconocida'],
        };
        ActivityLog::record('station_' . $action, 'Station #' . $id . ' (reseller)');
        set_flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect('reseller/stations/' . $id);
    }
}
