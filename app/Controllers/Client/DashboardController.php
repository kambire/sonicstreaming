<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Invoice;
use App\Models\Station;
use App\Models\StationStat;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $uid = (int) Auth::id();
        $stations = Station::forUser($uid);

        // Adjuntar ultimo snapshot a cada estacion
        foreach ($stations as &$s) {
            $s['latest'] = StationStat::latest((int) $s['id']);
        }
        unset($s);

        $pendingInvoices = Invoice::where(['user_id' => $uid, 'status' => 'pending']);
        $overdue = Invoice::where(['user_id' => $uid, 'status' => 'overdue']);

        $this->view('client/dashboard', [
            'title'    => 'Mi panel',
            'stations' => $stations,
            'pending'  => count($pendingInvoices) + count($overdue),
        ]);
    }
}
