<?php /** @var array $invoices */ ?>
<h5 class="mb-3">Mis facturas</h5>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Concepto</th><th>Monto</th><th>Vencimiento</th><th>Estado</th></tr></thead>
            <tbody>
            <?php if (!$invoices): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No tienes facturas.</td></tr>
            <?php endif; ?>
            <?php foreach ($invoices as $i): ?>
                <tr>
                    <td><?= e($i['concept']) ?></td>
                    <td><?= money((float) $i['amount']) ?></td>
                    <td><?= e($i['due_date']) ?></td>
                    <td><?= status_badge($i['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-3"><i class="bi bi-info-circle"></i> Para pagar una factura, contacta a tu proveedor.</p>
