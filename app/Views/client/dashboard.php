<?php /** @var array $stations; @var int $pending */ ?>
<?php if ($pending > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Tienes <strong><?= (int) $pending ?></strong> factura(s) por pagar.
        <a href="<?= url('client/invoices') ?>" class="alert-link">Ver facturas</a>.
    </div>
<?php endif; ?>

<h5 class="mb-3">Mis estaciones</h5>

<div class="row g-3">
    <?php if (!$stations): ?>
        <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">
            Aun no tienes estaciones asignadas. Contacta a tu proveedor.
        </div></div></div>
    <?php endif; ?>

    <?php foreach ($stations as $s): $latest = $s['latest'] ?? null; ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0"><i class="bi bi-broadcast-pin text-info"></i> <?= e($s['name']) ?></h6>
                        <?= status_badge($s['status']) ?>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Oyentes ahora</span>
                        <span class="text-info fw-bold"><?= (int) ($latest['current_listeners'] ?? 0) ?></span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Puerto</span><span><code><?= (int) $s['port'] ?></code></span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-3">
                        <span>AutoDJ</span><span><?= $s['autodj_enabled'] ? 'Activo' : 'Off' ?></span>
                    </div>
                    <a href="<?= url('client/stations/' . $s['id']) ?>" class="btn btn-sm btn-primary w-100">Administrar</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
