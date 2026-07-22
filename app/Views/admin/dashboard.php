<?php /** @var array $stats; @var array $recent */ ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value text-info"><?= (int) $stats['listeners_now'] ?></div>
                    <div class="stat-label">Oyentes ahora</div>
                </div>
                <i class="bi bi-headphones stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value text-success"><?= (int) $stats['stations_running'] ?>/<?= (int) $stats['stations_total'] ?></div>
                    <div class="stat-label">Estaciones en linea</div>
                </div>
                <i class="bi bi-broadcast stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value"><?= (int) $stats['clients'] ?></div>
                    <div class="stat-label">Clientes</div>
                </div>
                <i class="bi bi-people stat-icon"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-value <?= $stats['invoices_overdue'] > 0 ? 'text-danger' : '' ?>"><?= (int) $stats['invoices_overdue'] ?></div>
                    <div class="stat-label">Facturas vencidas</div>
                </div>
                <i class="bi bi-receipt stat-icon"></i>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Estaciones recientes</span>
        <a href="<?= url('admin/stations/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nueva estacion</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Estacion</th><th>Cliente</th><th>Puerto</th><th>AutoDJ</th><th>Estado</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$recent): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aun no hay estaciones. Crea la primera.</td></tr>
                <?php endif; ?>
                <?php foreach ($recent as $s): ?>
                    <tr>
                        <td><i class="bi bi-broadcast-pin text-info"></i> <?= e($s['name']) ?></td>
                        <td class="small"><?= e($s['owner_name']) ?></td>
                        <td><code><?= (int) $s['port'] ?></code></td>
                        <td><?= $s['autodj_enabled'] ? '<span class="badge bg-info">On</span>' : '<span class="badge bg-secondary">Off</span>' ?></td>
                        <td><?= status_badge($s['status']) ?></td>
                        <td class="text-end">
                            <a href="<?= url('admin/stations/' . $s['id']) ?>" class="btn btn-sm btn-outline-light">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
