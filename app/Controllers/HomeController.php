<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;

final class HomeController extends Controller
{
    /**
     * Redirige a cada usuario a su panel segun el rol.
     */
    public function index(Request $request): void
    {
        if (!Auth::check()) {
            redirect('login');
        }
        match (Auth::role()) {
            'admin'    => redirect('admin/dashboard'),
            'reseller' => redirect('reseller/dashboard'),
            default    => redirect('client/dashboard'),
        };
    }
}
