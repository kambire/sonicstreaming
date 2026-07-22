<?php
/** @var array|null $server */
$isEdit = $server !== null;
$action = $isEdit ? url('admin/servers/' . $server['id']) : url('admin/servers');
$driver = $server['driver'] ?? old('driver', 'mock');
$status = $server['status'] ?? old('status', 'active');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><?= $isEdit ? 'Editar servidor' : 'Nuevo servidor' ?></h5>
    <a href="<?= url('admin/servers') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
</div>

<div class="card"><div class="card-body">
    <form method="post" action="<?= $action ?>">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" required value="<?= e($server['name'] ?? old('name')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Host / IP</label>
                <input type="text" name="hostname" class="form-control" value="<?= e($server['hostname'] ?? old('hostname', '127.0.0.1')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Driver de control</label>
                <select name="driver" class="form-select">
                    <option value="mock"    <?= $driver === 'mock' ? 'selected' : '' ?>>mock (simulado / desarrollo)</option>
                    <option value="windows" <?= $driver === 'windows' ? 'selected' : '' ?>>windows (sc_serv.exe local)</option>
                    <option value="linux"   <?= $driver === 'linux' ? 'selected' : '' ?>>linux (systemd / produccion)</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="active"   <?= $status === 'active' ? 'selected' : '' ?>>Activo</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Puerto inicial</label>
                <input type="number" name="port_range_start" class="form-control" value="<?= e((string) ($server['port_range_start'] ?? old('port_range_start', '8000'))) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Puerto final</label>
                <input type="number" name="port_range_end" class="form-control" value="<?= e((string) ($server['port_range_end'] ?? old('port_range_end', '8100'))) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Max. streams</label>
                <input type="number" name="max_streams" class="form-control" value="<?= e((string) ($server['max_streams'] ?? old('max_streams', '50'))) ?>">
            </div>
        </div>
        <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
    </form>
</div></div>
