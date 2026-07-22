<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;

/**
 * Verifica el token CSRF en peticiones que modifican estado.
 */
final class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $token = $request->input('csrf_token');
            if (!Csrf::verify(is_string($token) ? $token : null)) {
                http_response_code(419);
                set_flash('danger', 'Sesion expirada o token invalido. Intenta de nuevo.');
                redirect('/');
            }
        }
    }
}
