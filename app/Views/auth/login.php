<div class="text-center mb-4">
    <div class="mb-2"><i class="bi bi-broadcast" style="font-size:2.5rem;color:var(--sonic-accent)"></i></div>
    <h4 class="mb-0"><?= e(env('APP_NAME', 'SonicStreaming')) ?></h4>
    <p class="text-muted small">Panel de administracion de streaming</p>
</div>

<form method="post" action="<?= url('login') ?>">
    <?= \App\Core\Csrf::field() ?>
    <div class="mb-3">
        <label class="form-label">Correo</label>
        <input type="email" name="email" class="form-control" value="<?= e(old('email')) ?>" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Contrasena</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100" type="submit">
        <i class="bi bi-box-arrow-in-right"></i> Ingresar
    </button>
</form>
