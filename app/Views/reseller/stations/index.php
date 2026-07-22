<?php /** @var array $stations */ ?>
<h5 class="mb-3">Estaciones de mis clientes</h5>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Estacion</th><th>Cliente</th><th>Puerto</th><th>Bitrate</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (!$stations): ?><tr><td colspan="6" class="text-center text-muted py-4">Sin estaciones.</td></tr><?php endif; ?>
            <?php foreach ($stations as $s): ?>
                <tr>
                    <td><i class="bi bi-broadcast-pin text-info"></i> <?= e($s['name']) ?></td>
                    <td class="small"><?= e($s['owner_name']) ?></td>
                    <td><code><?= (int) $s['port'] ?></code></td>
                    <td><?= (int) $s['max_bitrate'] ?>k</td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="text-end"><a href="<?= url('reseller/stations/' . $s['id']) ?>" class="btn btn-sm btn-outline-light">Administrar</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
