<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\User;

final class UserController extends Controller
{
    public function index(Request $request): void
    {
        $admins    = User::where(['role' => 'admin'], 'name ASC');
        $users     = User::where(['role' => 'client'], 'name ASC');
        $resellers = User::where(['role' => 'reseller'], 'name ASC');
        $this->view('admin/users/index', [
            'title'     => 'Usuarios del sistema',
            'admins'    => $admins,
            'clients'   => $users,
            'resellers' => $resellers,
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/users/form', [
            'title'     => 'Nuevo usuario',
            'user'      => null,
            'resellers' => User::resellers(),
        ]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request, null);
        if ($data === null) {
            $this->flashOld($request->all());
            redirect('admin/users/create');
        }
        $data['password_hash'] = password_hash((string) $request->input('password', ''), PASSWORD_DEFAULT);
        $id = User::create($data);
        ActivityLog::record('user_create', 'User #' . $id . ' (' . $data['role'] . ')');
        $this->clearOld();
        set_flash('success', 'Usuario creado.');
        redirect('admin/users');
    }

    public function edit(Request $request, string $id): void
    {
        $user = User::find((int) $id);
        if (!$user) {
            redirect('admin/users');
        }
        $this->view('admin/users/form', [
            'title'     => 'Editar usuario',
            'user'      => $user,
            'resellers' => User::resellers(),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $user = User::find((int) $id);
        if (!$user) {
            redirect('admin/users');
        }
        $data = $this->validate($request, (int) $id);
        if ($data === null) {
            $this->flashOld($request->all());
            redirect('admin/users/' . $id . '/edit');
        }
        $newPass = (string) $request->input('password', '');
        if ($newPass !== '') {
            $data['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
        }
        User::update((int) $id, $data);
        ActivityLog::record('user_update', 'User #' . $id);
        $this->clearOld();
        set_flash('success', 'Usuario actualizado.');
        redirect('admin/users');
    }

    public function destroy(Request $request, string $id): void
    {
        User::delete((int) $id);
        ActivityLog::record('user_delete', 'User #' . $id);
        set_flash('success', 'Usuario eliminado (sus estaciones tambien).');
        redirect('admin/users');
    }

    /** @return array<string,mixed>|null */
    private function validate(Request $request, ?int $ignoreId): ?array
    {
        $name  = $request->str('name');
        $email = strtolower($request->str('email'));
        $role  = $request->str('role', 'client');

        if ($name === '' || $email === '') {
            set_flash('danger', 'Nombre y correo son obligatorios.');
            return null;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('danger', 'Correo invalido.');
            return null;
        }
        if (User::emailExists($email, $ignoreId ?? 0)) {
            set_flash('danger', 'Ya existe un usuario con ese correo.');
            return null;
        }
        if (!in_array($role, ['client', 'reseller', 'admin'], true)) {
            $role = 'client';
        }
        if ($ignoreId === null && (string) $request->input('password', '') === '') {
            set_flash('danger', 'La contrasena es obligatoria para un usuario nuevo.');
            return null;
        }

        $resellerId = null;
        if ($role === 'client') {
            $rid = $request->int('reseller_id', 0);
            $resellerId = $rid > 0 ? $rid : null;
        }

        return [
            'name'         => $name,
            'email'        => $email,
            'role'         => $role,
            'reseller_id'  => $resellerId,
            'max_accounts' => $role === 'reseller' ? max(0, $request->int('max_accounts', 0)) : 0,
            'phone'        => $request->str('phone'),
            'status'       => $request->str('status', 'active') === 'suspended' ? 'suspended' : 'active',
        ];
    }
}
