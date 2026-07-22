<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

/**
 * Exige sesion iniciada.
 */
final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            set_flash('warning', 'Debes iniciar sesion para continuar.');
            redirect('login');
        }
    }
}
