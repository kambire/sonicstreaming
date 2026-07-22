<?php /** @var array $user */ ?>
<h5 class="mb-3">Mi perfil</h5>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Datos de la cuenta</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">Nombre:</span> <?= e($user['name']) ?></div>
                <div class="mb-2"><span class="text-muted">Correo:</span> <?= e($user['email']) ?></div>
                <div><span class="text-muted">Rol:</span> <span class="text-uppercase"><?= e($user['role']) ?></span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Cambiar contrasena</div>
            <div class="card-body">
                <form method="post" action="<?= url('profile/password') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="mb-3">
                        <label class="form-label">Contrasena actual</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nueva contrasena</label>
                            <input type="password" name="new_password" class="form-control" minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmar</label>
                            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                        </div>
                    </div>
                    <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-shield-lock"></i> Actualizar contrasena</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
