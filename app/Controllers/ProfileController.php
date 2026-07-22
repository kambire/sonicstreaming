<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\User;

final class ProfileController extends Controller
{
    public function show(Request $request): void
    {
        $this->view('profile/index', [
            'title' => 'Mi perfil',
            'user'  => Auth::user(),
        ]);
    }

    public function updatePassword(Request $request): void
    {
        $user = Auth::user();
        if (!$user) {
            redirect('login');
        }

        $current = (string) $request->input('current_password', '');
        $new     = (string) $request->input('new_password', '');
        $confirm = (string) $request->input('confirm_password', '');

        if (!password_verify($current, $user['password_hash'])) {
            set_flash('danger', 'La contrasena actual no es correcta.');
            redirect('profile');
        }
        if (strlen($new) < 8) {
            set_flash('danger', 'La nueva contrasena debe tener al menos 8 caracteres.');
            redirect('profile');
        }
        if ($new !== $confirm) {
            set_flash('danger', 'La confirmacion no coincide.');
            redirect('profile');
        }

        User::update((int) $user['id'], [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
        ]);
        ActivityLog::record('password_change', 'User #' . $user['id']);
        set_flash('success', 'Contrasena actualizada correctamente.');
        redirect('profile');
    }
}
