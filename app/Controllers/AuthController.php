<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        $this->view('auth/login', [], 'layouts/auth');
    }

    public function login(Request $request): void
    {
        $email = $request->str('email');
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            set_flash('danger', 'Ingresa tu correo y contrasena.');
            redirect('login');
        }

        // Rate limit basico por sesion
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] > 8) {
            set_flash('danger', 'Demasiados intentos. Espera unos minutos.');
            redirect('login');
        }

        if (!Auth::attempt($email, $password)) {
            ActivityLog::record('login_failed', $email);
            set_flash('danger', 'Credenciales invalidas o cuenta suspendida.');
            redirect('login');
        }

        $_SESSION['login_attempts'] = 0;
        ActivityLog::record('login_ok', $email);
        redirect('/');
    }

    public function logout(Request $request): void
    {
        ActivityLog::record('logout', (string) (Auth::user()['email'] ?? ''));
        Auth::logout();
        set_flash('info', 'Sesion cerrada.');
        redirect('login');
    }
}
