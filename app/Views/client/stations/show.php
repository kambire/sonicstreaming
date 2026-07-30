<?php
/** @var array $station; @var array|null $latest; @var string $adminPass */
echo \App\Core\View::renderPartial('partials/station_detail', [
    'station'       => $station,
    'owner'         => null,
    'latest'        => $latest,
    'adminPass'     => $adminPass,
    'base'          => 'client',
    'showSensitive' => true,
]);
?>

<div class="card mt-3">
    <div class="card-header fw-bold text-white"><i class="bi bi-sliders me-1 text-info"></i> Ajustes e Imágenes de la Estación</div>
    <div class="card-body">
        <form method="post" action="<?= url('client/stations/' . $station['id'] . '/settings') ?>" enctype="multipart/form-data">
            <?= \App\Core\Csrf::field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre / Título de la Radio</label>
                    <input type="text" name="name" class="form-control" value="<?= e($station['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Género / Estilo</label>
                    <input type="text" name="genre" class="form-control" value="<?= e($station['genre'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ícono / Logo Central (Imagen)</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <?php if (!empty($station['logo_url'])): ?>
                        <div class="form-text text-success small"><i class="bi bi-check-circle"></i> Logo cargado: <code><?= e($station['logo_url']) ?></code></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Imagen de Fondo / Portada</label>
                    <input type="file" name="background" class="form-control" accept="image/*">
                    <?php if (!empty($station['background_url'])): ?>
                        <div class="form-text text-success small"><i class="bi bi-check-circle"></i> Fondo cargado: <code><?= e($station['background_url']) ?></code></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nueva contraseña de fuente <span class="text-muted small">(vacío = no cambiar)</span></label>
                    <input type="text" name="source_password" class="form-control" placeholder="••••••">
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-primary btn-sm fw-bold"><i class="bi bi-check-lg"></i> Guardar Ajustes e Imágenes</button></div>
        </form>
    </div>
</div>
