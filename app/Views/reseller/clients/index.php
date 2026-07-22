<?php /** @var array $clients */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Mis clientes</h5>
    <a href="<?= url('reseller/clients/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-person-plus"></i> Nuevo cliente</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Telefono</th><th>Estado</th></tr></thead>
            <tbody>
            <?php if (!$clients): ?><tr><td colspan="4" class="text-center text-muted py-4">Aun no tienes clientes.</td></tr><?php endif; ?>
            <?php foreach ($clients as $c): ?>
                <tr>
                    <td><i class="bi bi-person"></i> <?= e($c['name']) ?></td>
                    <td class="small"><?= e($c['email']) ?></td>
                    <td class="small"><?= e($c['phone'] ?? '') ?></td>
                    <td><?= status_badge($c['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
