<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Station;
use App\Models\User;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $rid = (int) Auth::id();
        $clients = User::clientsOfReseller($rid);
        $stations = Station::forReseller($rid);

        $me = Auth::user();
        $quota = (int) ($me['max_accounts'] ?? 0);

        $running = 0;
        foreach ($stations as $s) {
            if ($s['status'] === 'running') {
                $running++;
            }
        }

        $this->view('reseller/dashboard', [
            'title'         => 'Panel reseller',
            'clientsCount'  => count($clients),
            'stationsCount' => count($stations),
            'running'       => $running,
            'quota'         => $quota,
            'stations'      => array_slice($stations, 0, 8),
        ]);
    }
}
