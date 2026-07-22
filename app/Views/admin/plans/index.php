<?php /** @var array $plans */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Planes</h5>
    <a href="<?= url('admin/plans/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nuevo plan</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr><th>Nombre</th><th>Bitrate</th><th>Oyentes</th><th>Disco</th><th>Precio</th><th>Ciclo</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (!$plans): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay planes.</td></tr>
            <?php endif; ?>
            <?php foreach ($plans as $p): ?>
                <tr>
                    <td><?= e($p['name']) ?></td>
                    <td><?= (int) $p['max_bitrate'] ?> kbps</td>
                    <td><?= (int) $p['max_listeners'] ?></td>
                    <td><?= (int) $p['disk_quota_mb'] ?> MB</td>
                    <td><?= money((float) $p['price']) ?></td>
                    <td class="small text-muted"><?= e($p['billing_cycle']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('admin/plans/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/plans/' . $p['id']) ?>" class="d-inline" onsubmit="return confirm('Eliminar este plan?')">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
