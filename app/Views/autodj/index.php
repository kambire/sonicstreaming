<?php
/** @var array $station; @var string $base; @var array $tracks; @var array $playlists; @var int $usageBytes; @var int $quotaMb */
$sid = (int) $station['id'];
$autodjUrl = url($base . '/stations/' . $sid . '/autodj');
$quotaBytes = $quotaMb * 1024 * 1024;
$pct = $quotaBytes > 0 ? min(100, round($usageBytes / $quotaBytes * 100)) : 0;
$running = ($station['autodj_status'] ?? 'stopped') === 'running';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <a href="<?= url($base . '/stations/' . $sid) ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Volver a la estacion</a>
        <h5 class="mb-0 mt-1"><i class="bi bi-music-note-list text-info"></i> AutoDJ · <?= e($station['name']) ?>
            <?= $running ? '<span class="badge bg-success">Reproduciendo</span>' : '<span class="badge bg-secondary">Detenido</span>' ?>
        </h5>
    </div>
    <div class="btn-group">
        <form method="post" action="<?= $autodjUrl ?>/start"><?= \App\Core\Csrf::field() ?>
            <button class="btn btn-sm btn-success" <?= $running ? 'disabled' : '' ?>><i class="bi bi-play-fill"></i> Iniciar AutoDJ</button></form>
        <form method="post" action="<?= $autodjUrl ?>/stop"><?= \App\Core\Csrf::field() ?>
            <button class="btn btn-sm btn-danger" <?= $running ? '' : 'disabled' ?>><i class="bi bi-stop-fill"></i> Detener</button></form>
    </div>
</div>

<div class="row g-3">
    <!-- Biblioteca -->
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Biblioteca de musica</span>
                <span class="small text-muted">
                    <?= human_size($usageBytes) ?><?= $quotaMb > 0 ? ' / ' . $quotaMb . ' MB' : '' ?>
                </span>
            </div>
            <div class="card-body">
                <?php if ($quotaMb > 0): ?>
                    <div class="progress mb-3" style="height:6px;">
                        <div class="progress-bar <?= $pct > 90 ? 'bg-danger' : 'bg-info' ?>" style="width: <?= $pct ?>%"></div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= $autodjUrl ?>/upload" enctype="multipart/form-data" class="mb-3">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="input-group">
                        <input type="file" name="tracks[]" class="form-control" accept="audio/*" multiple required>
                        <button class="btn btn-primary"><i class="bi bi-upload"></i> Subir música</button>
                    </div>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Puedes seleccionar <strong>múltiples archivos a la vez</strong> (manteniendo presionado Ctrl o Shift).<br>
                        Formatos: mp3, aac, m4a, ogg, flac, wav. Recomendación: nombra los archivos como "Artista - Título".
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead><tr><th>Titulo</th><th>Artista</th><th>Tamano</th><th></th></tr></thead>
                        <tbody>
                        <?php if (!$tracks): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Aun no hay pistas. Sube tu musica.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($tracks as $t): ?>
                            <tr>
                                <td class="text-truncate" style="max-width:220px"><?= e($t['title'] ?: $t['original_name']) ?></td>
                                <td class="small text-muted"><?= e($t['artist'] ?? '') ?></td>
                                <td class="small"><?= human_size((int) $t['filesize']) ?></td>
                                <td class="text-end">
                                    <form method="post" action="<?= $autodjUrl ?>/tracks/<?= (int) $t['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Eliminar pista?')">
                                        <?= \App\Core\Csrf::field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Playlists -->
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header">Nueva playlist</div>
            <div class="card-body">
                <form method="post" action="<?= $autodjUrl ?>/playlists">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="mb-2">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nombre de la playlist" required>
                    </div>
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <input type="number" name="weight" class="form-control form-control-sm" value="1" min="1" title="Peso en la rotacion">
                        </div>
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="shuffle" id="sh" value="1" checked>
                                <label class="form-check-label small" for="sh">Aleatorio</label>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-primary">Crear</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php foreach ($playlists as $pl): $items = \App\Models\Playlist::items((int) $pl['id']); ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-collection-play"></i> <?= e($pl['name']) ?>
                        <span class="badge bg-secondary">x<?= (int) $pl['weight'] ?></span>
                        <?= (int) $pl['shuffle'] === 1 ? '<span class="badge bg-info">shuffle</span>' : '' ?>
                    </span>
                    <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/delete" onsubmit="return confirm('Eliminar playlist?')">
                        <?= \App\Core\Csrf::field() ?>
                        <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if ($tracks): ?>
                    <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/tracks" class="input-group input-group-sm mb-2">
                        <?= \App\Core\Csrf::field() ?>
                        <select name="track_id" class="form-select">
                            <?php foreach ($tracks as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= e($t['title'] ?: $t['original_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-info"><i class="bi bi-plus-lg"></i> Agregar</button>
                    </form>
                    <?php endif; ?>

                    <?php if (!$items): ?>
                        <p class="small text-muted mb-0">Playlist vacia.</p>
                    <?php else: ?>
                        <ol class="small mb-0 ps-3">
                        <?php foreach ($items as $it): ?>
                            <li class="d-flex justify-content-between align-items-center">
                                <span class="text-truncate" style="max-width:200px"><?= e($it['title'] ?: $it['original_name']) ?></span>
                                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/items/<?= (int) $it['item_id'] ?>/remove">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<p class="text-muted small mt-2"><i class="bi bi-info-circle"></i> Tras cambiar musica o playlists, reinicia el AutoDJ para regenerar el script y aplicar los cambios.</p>
