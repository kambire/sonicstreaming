<?php /** @var int $clientsCount; @var int $stationsCount; @var int $running; @var int $quota; @var array $stations */ ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card stat-card"><div class="d-flex justify-content-between align-items-center">
            <div><div class="stat-value"><?= $clientsCount ?><?= $quota > 0 ? '<span class="fs-6 text-muted">/' . $quota . '</span>' : '' ?></div><div class="stat-label">Clientes</div></div>
            <i class="bi bi-people stat-icon"></i>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card stat-card"><div class="d-flex justify-content-between align-items-center">
            <div><div class="stat-value text-success"><?= $running ?>/<?= $stationsCount ?></div><div class="stat-label">Estaciones en linea</div></div>
            <i class="bi bi-broadcast stat-icon"></i>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Estaciones recientes</span>
        <a href="<?= url('reseller/clients/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> Nuevo cliente</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Estacion</th><th>Cliente</th><th>Puerto</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (!$stations): ?><tr><td colspan="5" class="text-center text-muted py-4">Sin estaciones aun.</td></tr><?php endif; ?>
            <?php foreach ($stations as $s): ?>
                <tr>
                    <td><i class="bi bi-broadcast-pin text-info"></i> <?= e($s['name']) ?></td>
                    <td class="small"><?= e($s['owner_name']) ?></td>
                    <td><code><?= (int) $s['port'] ?></code></td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="text-end"><a href="<?= url('reseller/stations/' . $s['id']) ?>" class="btn btn-sm btn-outline-light">Ver</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
