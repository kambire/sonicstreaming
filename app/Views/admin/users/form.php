<?php
/** @var array|null $user; @var array $resellers */
$isEdit = $user !== null;
$action = $isEdit ? url('admin/users/' . $user['id']) : url('admin/users');
$role   = $user['role'] ?? old('role', 'client');
$status = $user['status'] ?? old('status', 'active');
$resellerId = (string) ($user['reseller_id'] ?? old('reseller_id', ''));
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h5>
    <a href="<?= url('admin/users') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
</div>

<div class="card"><div class="card-body">
    <form method="post" action="<?= $action ?>">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" required value="<?= e($user['name'] ?? old('name')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Correo</label>
                <input type="email" name="email" class="form-control" required value="<?= e($user['email'] ?? old('email')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Rol</label>
                <select name="role" class="form-select" id="roleSelect">
                    <option value="client"   <?= $role === 'client' ? 'selected' : '' ?>>Cliente</option>
                    <option value="reseller" <?= $role === 'reseller' ? 'selected' : '' ?>>Reseller</option>
                    <option value="admin"    <?= $role === 'admin' ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <option value="active"    <?= $status === 'active' ? 'selected' : '' ?>>Activo</option>
                    <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspendido</option>
                </select>
            </div>

            <div class="col-md-6 role-client">
                <label class="form-label">Pertenece al reseller (opcional)</label>
                <select name="reseller_id" class="form-select">
                    <option value="">— Ninguno (cliente directo) —</option>
                    <?php foreach ($resellers as $r): ?>
                        <option value="<?= (int) $r['id'] ?>" <?= $resellerId === (string) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 role-reseller">
                <label class="form-label">Cuota de cuentas (0 = ilimitada)</label>
                <input type="number" name="max_accounts" class="form-control" min="0" value="<?= e((string) ($user['max_accounts'] ?? old('max_accounts', '0'))) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Telefono</label>
                <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? old('phone')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contrasena <?= $isEdit ? '<span class="text-muted small">(dejar vacio para no cambiar)</span>' : '' ?></label>
                <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
            </div>
        </div>
        <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
    </form>
</div></div>

<script>
(function () {
    const sel = document.getElementById('roleSelect');
    function toggle() {
        const isReseller = sel.value === 'reseller';
        document.querySelectorAll('.role-reseller').forEach(el => el.style.display = isReseller ? '' : 'none');
        document.querySelectorAll('.role-client').forEach(el => el.style.display = isReseller ? 'none' : '');
    }
    sel.addEventListener('change', toggle); toggle();
})();
</script>
