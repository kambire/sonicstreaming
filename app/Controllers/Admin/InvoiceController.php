<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Station;
use App\Models\User;
use App\Services\BillingService;

final class InvoiceController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/invoices/index', [
            'title'    => 'Facturas',
            'invoices' => Invoice::allWithUser(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/invoices/form', [
            'title'    => 'Nueva factura',
            'clients'  => User::where(['role' => 'client'], 'name ASC'),
            'stations' => Station::allWithOwner(),
        ]);
    }

    public function store(Request $request): void
    {
        $userId = $request->int('user_id', 0);
        $amount = (float) $request->input('amount', 0);
        $due    = $request->str('due_date');

        if ($userId <= 0 || $amount <= 0 || $due === '') {
            set_flash('danger', 'Cliente, monto y fecha de vencimiento son obligatorios.');
            redirect('admin/invoices/create');
        }
        if (!\DateTime::createFromFormat('Y-m-d', $due)) {
            set_flash('danger', 'Fecha de vencimiento invalida (formato AAAA-MM-DD).');
            redirect('admin/invoices/create');
        }

        $stationId = $request->int('station_id', 0) ?: null;

        $id = Invoice::create([
            'user_id'    => $userId,
            'station_id' => $stationId,
            'concept'    => $request->str('concept', 'Servicio de streaming'),
            'amount'     => $amount,
            'due_date'   => $due,
            'status'     => 'pending',
        ]);
        ActivityLog::record('invoice_create', 'Invoice #' . $id);
        set_flash('success', 'Factura creada.');
        redirect('admin/invoices');
    }

    public function markPaid(Request $request, string $id): void
    {
        $invoice = Invoice::find((int) $id);
        if (!$invoice) {
            redirect('admin/invoices');
        }
        Invoice::update((int) $id, [
            'status'  => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
        // Reactivar estaciones del cliente si ya no debe nada
        $reactivated = (new BillingService())->reactivateUser((int) $invoice['user_id']);
        ActivityLog::record('invoice_paid', 'Invoice #' . $id);
        set_flash('success', 'Factura marcada como pagada.' . ($reactivated > 0 ? " Se reactivaron {$reactivated} estacion(es)." : ''));
        redirect('admin/invoices');
    }

    public function destroy(Request $request, string $id): void
    {
        Invoice::delete((int) $id);
        ActivityLog::record('invoice_delete', 'Invoice #' . $id);
        set_flash('success', 'Factura eliminada.');
        redirect('admin/invoices');
    }
}
