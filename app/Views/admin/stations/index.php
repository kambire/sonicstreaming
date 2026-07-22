<?php /** @var array $stations */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Estaciones</h5>
    <a href="<?= url('admin/stations/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nueva estacion</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr><th>Estacion</th><th>Cliente</th><th>Servidor</th><th>Puerto</th><th>Bitrate</th><th>AutoDJ</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (!$stations): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Aun no hay estaciones.</td></tr>
            <?php endif; ?>
            <?php foreach ($stations as $s): ?>
                <tr>
                    <td><i class="bi bi-broadcast-pin text-info"></i> <?= e($s['name']) ?></td>
                    <td class="small"><?= e($s['owner_name']) ?></td>
                    <td class="small text-muted"><?= e($s['server_name']) ?></td>
                    <td><code><?= (int) $s['port'] ?></code></td>
                    <td><?= (int) $s['max_bitrate'] ?>k</td>
                    <td><?= $s['autodj_enabled'] ? '<span class="badge bg-info">On</span>' : '<span class="badge bg-secondary">Off</span>' ?></td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('admin/stations/' . $s['id']) ?>" class="btn btn-sm btn-outline-light">Administrar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
