<?php /** @var array $invoices */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Facturas</h5>
    <a href="<?= url('admin/invoices/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nueva factura</a>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>#</th><th>Cliente</th><th>Concepto</th><th>Monto</th><th>Vencimiento</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (!$invoices): ?><tr><td colspan="7" class="text-center text-muted py-4">No hay facturas.</td></tr><?php endif; ?>
            <?php foreach ($invoices as $i): ?>
                <tr>
                    <td>#<?= (int) $i['id'] ?></td>
                    <td class="small"><?= e($i['user_name']) ?></td>
                    <td class="small"><?= e($i['concept']) ?></td>
                    <td><?= money((float) $i['amount']) ?></td>
                    <td><?= e($i['due_date']) ?></td>
                    <td><?= status_badge($i['status']) ?></td>
                    <td class="text-end">
                        <?php if ($i['status'] !== 'paid'): ?>
                            <form method="post" action="<?= url('admin/invoices/' . $i['id'] . '/pay') ?>" class="d-inline">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Pagar</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="<?= url('admin/invoices/' . $i['id']) ?>" class="d-inline" onsubmit="return confirm('Eliminar factura?')">
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
