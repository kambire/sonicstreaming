<?php

declare(strict_types=1);

namespace App\Controllers\Client;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Invoice;

final class InvoiceController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('client/invoices', [
            'title'    => 'Mis facturas',
            'invoices' => Invoice::forUser((int) Auth::id()),
        ]);
    }
}
