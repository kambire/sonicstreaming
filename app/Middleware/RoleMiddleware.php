<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

/**
 * Restringe el acceso por rol. Se instancia con los roles permitidos.
 *
 * Uso en rutas:  new RoleMiddleware('admin')  /  new RoleMiddleware('admin','reseller')
 */
final class RoleMiddleware
{
    /** @var array<int,string> */
    private array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }

    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            redirect('login');
        }
        if (!Auth::hasRole(...$this->roles)) {
            http_response_code(403);
            echo \App\Core\View::render('errors/403', [], 'layouts/blank');
            exit;
        }
    }
}
