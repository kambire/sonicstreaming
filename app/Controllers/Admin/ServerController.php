<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Server;

final class ServerController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/servers/index', [
            'title'   => 'Servidores',
            'servers' => Server::all('name ASC'),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/servers/form', ['title' => 'Nuevo servidor', 'server' => null]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request);
        if ($data === null) {
            $this->flashOld($request->all());
            redirect('admin/servers/create');
        }
        $id = Server::create($data);
        ActivityLog::record('server_create', 'Server #' . $id);
        $this->clearOld();
        set_flash('success', 'Servidor creado.');
        redirect('admin/servers');
    }

    public function edit(Request $request, string $id): void
    {
        $server = Server::find((int) $id);
        if (!$server) {
            redirect('admin/servers');
        }
        $this->view('admin/servers/form', ['title' => 'Editar servidor', 'server' => $server]);
    }

    public function update(Request $request, string $id): void
    {
        if (!Server::find((int) $id)) {
            redirect('admin/servers');
        }
        $data = $this->validate($request);
        if ($data === null) {
            $this->flashOld($request->all());
            redirect('admin/servers/' . $id . '/edit');
        }
        Server::update((int) $id, $data);
        ActivityLog::record('server_update', 'Server #' . $id);
        $this->clearOld();
        set_flash('success', 'Servidor actualizado.');
        redirect('admin/servers');
    }

    public function destroy(Request $request, string $id): void
    {
        if (Server::count('id = ?', [(int) $id]) > 0) {
            $stations = \App\Models\Station::where(['server_id' => (int) $id]);
            if ($stations) {
                set_flash('danger', 'No se puede eliminar: el servidor tiene estaciones asignadas.');
                redirect('admin/servers');
            }
        }
        Server::delete((int) $id);
        ActivityLog::record('server_delete', 'Server #' . $id);
        set_flash('success', 'Servidor eliminado.');
        redirect('admin/servers');
    }

    /** @return array<string,mixed>|null */
    private function validate(Request $request): ?array
    {
        $name = $request->str('name');
        if ($name === '') {
            set_flash('danger', 'El nombre es obligatorio.');
            return null;
        }
        $driver = $request->str('driver', 'mock');
        if (!in_array($driver, ['mock', 'windows', 'linux'], true)) {
            $driver = 'mock';
        }
        $start = $request->int('port_range_start', 8000);
        $end   = $request->int('port_range_end', 8100);
        if ($end <= $start) {
            set_flash('danger', 'El fin del rango de puertos debe ser mayor que el inicio.');
            return null;
        }
        return [
            'name'             => $name,
            'hostname'         => $request->str('hostname', '127.0.0.1'),
            'driver'           => $driver,
            'port_range_start' => $start,
            'port_range_end'   => $end,
            'max_streams'      => max(1, $request->int('max_streams', 50)),
            'status'           => $request->str('status', 'active') === 'inactive' ? 'inactive' : 'active',
        ];
    }
}
