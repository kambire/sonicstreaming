<?php
/** @var array|null $plan */
$isEdit = $plan !== null;
$action = $isEdit ? url('admin/plans/' . $plan['id']) : url('admin/plans');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><?= $isEdit ? 'Editar plan' : 'Nuevo plan' ?></h5>
    <a href="<?= url('admin/plans') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?= $action ?>">
            <?= \App\Core\Csrf::field() ?>
            <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

            <div class="mb-3">
                <label class="form-label">Nombre del plan</label>
                <input type="text" name="name" class="form-control" required
                       value="<?= e($plan['name'] ?? old('name')) ?>">
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Bitrate maximo (kbps)</label>
                    <input type="number" name="max_bitrate" class="form-control" min="8" step="8"
                           value="<?= e((string) ($plan['max_bitrate'] ?? old('max_bitrate', '128'))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Oyentes maximos</label>
                    <input type="number" name="max_listeners" class="form-control" min="1"
                           value="<?= e((string) ($plan['max_listeners'] ?? old('max_listeners', '100'))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Disco AutoDJ (MB)</label>
                    <input type="number" name="disk_quota_mb" class="form-control" min="0"
                           value="<?= e((string) ($plan['disk_quota_mb'] ?? old('disk_quota_mb', '500'))) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Precio</label>
                    <input type="number" name="price" class="form-control" min="0" step="0.01"
                           value="<?= e((string) ($plan['price'] ?? old('price', '0'))) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ciclo de facturacion</label>
                    <?php $cycle = $plan['billing_cycle'] ?? old('billing_cycle', 'monthly'); ?>
                    <select name="billing_cycle" class="form-select">
                        <option value="monthly"   <?= $cycle === 'monthly' ? 'selected' : '' ?>>Mensual</option>
                        <option value="quarterly" <?= $cycle === 'quarterly' ? 'selected' : '' ?>>Trimestral</option>
                        <option value="yearly"    <?= $cycle === 'yearly' ? 'selected' : '' ?>>Anual</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>
