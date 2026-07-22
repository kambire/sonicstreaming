<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Station;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $db = Database::connection();

        $stats = [
            'stations_total'   => (int) $db->query('SELECT COUNT(*) FROM stations')->fetchColumn(),
            'stations_running' => (int) $db->query("SELECT COUNT(*) FROM stations WHERE status='running'")->fetchColumn(),
            'clients'          => (int) $db->query("SELECT COUNT(*) FROM users WHERE role='client'")->fetchColumn(),
            'resellers'        => (int) $db->query("SELECT COUNT(*) FROM users WHERE role='reseller'")->fetchColumn(),
            'servers'          => (int) $db->query('SELECT COUNT(*) FROM servers')->fetchColumn(),
            'invoices_overdue' => (int) $db->query("SELECT COUNT(*) FROM invoices WHERE status='overdue'")->fetchColumn(),
        ];

        // Oyentes actuales: suma del ultimo snapshot de cada estacion.
        $listeners = (int) $db->query(
            'SELECT COALESCE(SUM(ss.current_listeners),0)
             FROM station_stats ss
             JOIN (SELECT station_id, MAX(id) AS max_id FROM station_stats GROUP BY station_id) last
               ON last.max_id = ss.id'
        )->fetchColumn();
        $stats['listeners_now'] = $listeners;

        $recent = Station::allWithOwner();
        $recent = array_slice($recent, 0, 8);

        $this->view('admin/dashboard', [
            'title'   => 'Panel de administracion',
            'stats'   => $stats,
            'recent'  => $recent,
        ]);
    }
}
