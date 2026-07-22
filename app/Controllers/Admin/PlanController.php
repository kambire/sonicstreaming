<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityLog;
use App\Models\Plan;

final class PlanController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/plans/index', [
            'title' => 'Planes',
            'plans' => Plan::all('name ASC'),
        ]);
    }

    public function create(Request $request): void
    {
        $this->view('admin/plans/form', ['title' => 'Nuevo plan', 'plan' => null]);
    }

    public function store(Request $request): void
    {
        $data = $this->validate($request);
        if ($data === null) {
            $this->flashOld($request->all());
            redirect('admin/plans/create');
        }
        $id = Plan::create($data);
        ActivityLog::record('plan_create', 'Plan #' . $id);
        $this->clearOld();
        set_flash('success', 'Plan creado.');
        redirect('admin/plans');
    }

    public function edit(Request $request, string $id): void
    {
        $plan = Plan::find((int) $id);
        if (!$plan) {
            set_flash('danger', 'Plan no encontrado.');
            redirect('admin/plans');
        }
        $this->view('admin/plans/form', ['title' => 'Editar plan', 'plan' => $plan]);
    }

    public function update(Request $request, string $id): void
    {
        $plan = Plan::find((int) $id);
        if (!$plan) {
            redirect('admin/plans');
        }
        $data = $this->validate($request);
        if ($data === null) {
            $this->flashOld($request->all());
            redirect('admin/plans/' . $id . '/edit');
        }
        Plan::update((int) $id, $data);
        ActivityLog::record('plan_update', 'Plan #' . $id);
        $this->clearOld();
        set_flash('success', 'Plan actualizado.');
        redirect('admin/plans');
    }

    public function destroy(Request $request, string $id): void
    {
        Plan::delete((int) $id);
        ActivityLog::record('plan_delete', 'Plan #' . $id);
        set_flash('success', 'Plan eliminado.');
        redirect('admin/plans');
    }

    /** @return array<string,mixed>|null */
    private function validate(Request $request): ?array
    {
        $name = $request->str('name');
        if ($name === '') {
            set_flash('danger', 'El nombre es obligatorio.');
            return null;
        }
        $cycle = $request->str('billing_cycle', 'monthly');
        if (!in_array($cycle, ['monthly', 'quarterly', 'yearly'], true)) {
            $cycle = 'monthly';
        }
        return [
            'name'          => $name,
            'max_bitrate'   => max(8, $request->int('max_bitrate', 128)),
            'max_listeners' => max(1, $request->int('max_listeners', 100)),
            'disk_quota_mb' => max(0, $request->int('disk_quota_mb', 500)),
            'price'         => (float) $request->input('price', 0),
            'billing_cycle' => $cycle,
        ];
    }
}
