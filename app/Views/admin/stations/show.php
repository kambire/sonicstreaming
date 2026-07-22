<?php
/** @var array $station; @var array|null $owner; @var array|null $latest; @var string $adminPass */
echo \App\Core\View::renderPartial('partials/station_detail', [
    'station'       => $station,
    'owner'         => $owner,
    'latest'        => $latest,
    'adminPass'     => $adminPass,
    'base'          => 'admin',
    'showSensitive' => true,
]);
?>
<div class="mt-3">
    <form method="post" action="<?= url('admin/stations/' . $station['id']) ?>" onsubmit="return confirm('Eliminar esta estacion definitivamente?')">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar estacion</button>
    </form>
</div>
