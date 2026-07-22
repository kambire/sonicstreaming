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
    <div class="card-header">Ajustes de la estacion</div>
    <div class="card-body">
        <form method="post" action="<?= url('client/stations/' . $station['id'] . '/settings') ?>">
            <?= \App\Core\Csrf::field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre / titulo</label>
                    <input type="text" name="name" class="form-control" value="<?= e($station['name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Genero</label>
                    <input type="text" name="genre" class="form-control" value="<?= e($station['genre'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nueva contrasena de fuente <span class="text-muted small">(vacio = no cambiar)</span></label>
                    <input type="text" name="source_password" class="form-control" placeholder="••••••">
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Guardar ajustes</button></div>
        </form>
    </div>
</div>
