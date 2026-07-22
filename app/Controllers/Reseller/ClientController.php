<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\User;

final class ClientController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('reseller/clients/index', [
            'title'   => 'Mis clientes',
            'clients' => User::clientsOfReseller((int) Auth::id()),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('reseller/clients/form', ['title' => 'Nuevo cliente']);
    }

    public function store(Request $request): void
    {
        $rid = (int) Auth::id();
        $me = Auth::user();
        $quota = (int) ($me['max_accounts'] ?? 0);

        if ($quota > 0 && count(User::clientsOfReseller($rid)) >= $quota) {
            set_flash('danger', 'Alcanzaste tu cuota de cuentas de cliente.');
            redirect('reseller/clients');
        }

        $name  = $request->str('name');
        $email = strtolower($request->str('email'));
        $pass  = (string) $request->input('password', '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
            set_flash('danger', 'Nombre, correo valido y contrasena son obligatorios.');
            $this->flashOld($request->all());
            redirect('reseller/clients/create');
        }
        if (User::emailExists($email)) {
            set_flash('danger', 'Ya existe un usuario con ese correo.');
            redirect('reseller/clients/create');
        }

        $id = User::create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role'          => 'client',
            'reseller_id'   => $rid,
            'phone'         => $request->str('phone'),
            'status'        => 'active',
        ]);
        ActivityLog::record('reseller_client_create', 'User #' . $id);
        $this->clearOld();
        set_flash('success', 'Cliente creado.');
        redirect('reseller/clients');
    }
}
