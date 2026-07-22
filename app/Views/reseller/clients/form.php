<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Nuevo cliente</h5>
    <a href="<?= url('reseller/clients') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
</div>
<div class="card"><div class="card-body">
    <form method="post" action="<?= url('reseller/clients') ?>">
        <?= \App\Core\Csrf::field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" required value="<?= e(old('name')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Correo</label>
                <input type="email" name="email" class="form-control" required value="<?= e(old('email')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Telefono</label>
                <input type="text" name="phone" class="form-control" value="<?= e(old('phone')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contrasena</label>
                <input type="password" name="password" class="form-control" required>
            </div>
        </div>
        <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Crear cliente</button></div>
    </form>
</div></div>
