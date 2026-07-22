<?php /** @var array $clients; @var array $resellers */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Clientes y resellers</h5>
    <a href="<?= url('admin/users/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> Nuevo usuario</a>
</div>

<div class="card mb-4">
    <div class="card-header">Resellers</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Cuota</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (!$resellers): ?><tr><td colspan="5" class="text-center text-muted py-3">Sin resellers.</td></tr><?php endif; ?>
            <?php foreach ($resellers as $u): ?>
                <tr>
                    <td><i class="bi bi-person-badge"></i> <?= e($u['name']) ?></td>
                    <td class="small"><?= e($u['email']) ?></td>
                    <td><?= (int) $u['max_accounts'] === 0 ? '<span class="text-muted">ilimitada</span>' : (int) $u['max_accounts'] ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('admin/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/users/' . $u['id']) ?>" class="d-inline" onsubmit="return confirm('Eliminar usuario y sus estaciones?')">
                            <?= \App\Core\Csrf::field() ?><input type="hidden" name="_method" value="DELETE">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">Clientes</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Telefono</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (!$clients): ?><tr><td colspan="5" class="text-center text-muted py-3">Sin clientes.</td></tr><?php endif; ?>
            <?php foreach ($clients as $u): ?>
                <tr>
                    <td><i class="bi bi-person"></i> <?= e($u['name']) ?></td>
                    <td class="small"><?= e($u['email']) ?></td>
                    <td class="small"><?= e($u['phone'] ?? '') ?></td>
                    <td><?= status_badge($u['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('admin/users/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/users/' . $u['id']) ?>" class="d-inline" onsubmit="return confirm('Eliminar usuario y sus estaciones?')">
                            <?= \App\Core\Csrf::field() ?><input type="hidden" name="_method" value="DELETE">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
