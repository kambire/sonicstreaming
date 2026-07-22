<?php /** @var array $clients; @var array $stations */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Nueva factura</h5>
    <a href="<?= url('admin/invoices') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
</div>
<div class="card"><div class="card-body">
    <form method="post" action="<?= url('admin/invoices') ?>">
        <?= \App\Core\Csrf::field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Cliente</label>
                <select name="user_id" class="form-select" required>
                    <option value="">— Selecciona —</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Estacion (opcional)</label>
                <select name="station_id" class="form-select">
                    <option value="">— Ninguna (suspende todas si vence) —</option>
                    <?php foreach ($stations as $s): ?>
                        <option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?> — <?= e($s['owner_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Concepto</label>
                <input type="text" name="concept" class="form-control" value="Servicio de streaming mensual">
            </div>
            <div class="col-md-3">
                <label class="form-label">Monto</label>
                <input type="number" name="amount" class="form-control" min="0" step="0.01" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Vencimiento</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>
        </div>
        <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Crear factura</button></div>
    </form>
</div></div>
