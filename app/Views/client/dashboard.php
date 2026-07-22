<?php /** @var array $stations; @var int $pending */
$totalStations = count($stations);
$activeStations = count(array_filter($stations, fn($s) => ($s['status'] ?? '') === 'running'));
$totalListeners = array_reduce($stations, fn($carry, $s) => $carry + (int)($s['latest']['current_listeners'] ?? 0), 0);
?>

<?php if ($pending > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-2 mb-4" style="background: rgba(255, 193, 7, 0.15); border-left: 4px solid #ffc107 !important;">
        <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
        <div>
            <strong>Aviso de facturación:</strong> Tienes <strong><?= (int) $pending ?></strong> factura(s) pendiente(s) por pagar.
            <a href="<?= url('client/invoices') ?>" class="alert-link ms-2">Ver y abonar facturas &raquo;</a>
        </div>
    </div>
<?php endif; ?>

<!-- TARJETAS DE RESUMEN GENERAL -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm bg-dark bg-opacity-50" style="border-left: 4px solid #0dcaf0 !important;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded bg-info bg-opacity-10 p-3 me-3 text-info">
                    <i class="bi bi-broadcast fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Emisoras de Radio</div>
                    <div class="fs-4 fw-bold text-white"><?= $activeStations ?> <span class="fs-6 text-muted font-normal">/ <?= $totalStations ?> activas</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm bg-dark bg-opacity-50" style="border-left: 4px solid #10b981 !important;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded bg-success bg-opacity-10 p-3 me-3 text-success">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Oyentes en Vivo</div>
                    <div class="fs-4 fw-bold text-success"><?= $totalListeners ?> <span class="fs-6 text-muted font-normal">conectados ahora</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card border-0 shadow-sm bg-dark bg-opacity-50" style="border-left: 4px solid #6f42c1 !important;">
            <div class="card-body p-3 d-flex align-items-center">
                <div class="rounded bg-purple bg-opacity-10 p-3 me-3 text-purple" style="color: #a855f7;">
                    <i class="bi bi-soundwave fs-3"></i>
                </div>
                <div>
                    <div class="text-muted small fw-bold text-uppercase">Motor AutoDJ</div>
                    <div class="fs-4 fw-bold text-white">Liquidsoap 2.2 <span class="badge bg-purple bg-opacity-20 text-purple border border-purple ms-1" style="color: #c084fc; font-size:10px;">SSL Encoders</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 text-white fw-bold"><i class="bi bi-collection-play text-info me-2"></i>Mis Emisoras de Radio</h5>
</div>

<div class="row g-3">
    <?php if (!$stations): ?>
        <div class="col-12"><div class="card border-0 bg-dark bg-opacity-50"><div class="card-body text-center text-muted py-5">
            <i class="bi bi-broadcast fs-1 d-block mb-2 text-secondary"></i>
            Aún no tienes estaciones asignadas. Contacta a tu proveedor para activar tu emisora de radio.
        </div></div></div>
    <?php endif; ?>

    <?php foreach ($stations as $s): $latest = $s['latest'] ?? null; ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 bg-dark bg-opacity-50 shadow-sm hover-shadow transition">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                            <h6 class="mb-0 fw-bold text-white"><i class="bi bi-broadcast-pin text-info me-1"></i> <?= e($s['name']) ?></h6>
                            <?= status_badge($s['status']) ?>
                        </div>
                        
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span><i class="bi bi-people me-1"></i> Oyentes ahora</span>
                            <span class="text-info fw-bold fs-6"><?= (int) ($latest['current_listeners'] ?? 0) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span><i class="bi bi-ethernet me-1"></i> Puerto Emisión</span>
                            <code class="text-light"><?= (int) $s['port'] ?></code>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mb-3">
                            <span><i class="bi bi-disc me-1"></i> Estado AutoDJ</span>
                            <span><?= $s['autodj_enabled'] ? '<span class="badge bg-info text-dark">Habilitado</span>' : '<span class="badge bg-secondary">Off</span>' ?></span>
                        </div>
                    </div>
                    
                    <a href="<?= url('client/stations/' . $s['id']) ?>" class="btn btn-sm btn-info text-dark fw-bold w-100 shadow-sm">
                        <i class="bi bi-sliders"></i> Administrar Emisora &raquo;
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
