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
        <a href="<?= url($base . '/stations/' . $sid) ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Volver a la estación</a>
        <h5 class="mb-0 mt-1"><i class="bi bi-music-note-list text-info"></i> Gestión de AutoDJ y Playlists · <?= e($station['name']) ?>
            <?= $running ? '<span class="badge bg-success"><i class="bi bi-play-circle-fill"></i> Transmitiendo al aire</span>' : '<span class="badge bg-secondary">Detenido</span>' ?>
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
    <!-- Biblioteca de música -->
    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-disc"></i> Biblioteca de música (<?= count($tracks) ?> canciones)</span>
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

                <!-- Subir música -->
                <form method="post" action="<?= $autodjUrl ?>/upload" enctype="multipart/form-data" class="mb-4 p-3 border rounded bg-dark-subtle">
                    <?= \App\Core\Csrf::field() ?>
                    <label class="form-label fw-bold"><i class="bi bi-cloud-upload text-primary"></i> Subir canciones a la biblioteca</label>
                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <input type="file" name="tracks[]" class="form-control" accept="audio/*" multiple required>
                        </div>
                        <div class="col-md-5">
                            <select name="playlist_id" class="form-select">
                                <option value="0">— Solo biblioteca —</option>
                                <?php foreach ($playlists as $pl): ?>
                                    <option value="<?= (int) $pl['id'] ?>" <?= (int) $pl['is_active'] === 1 ? 'selected' : '' ?>>Auto-agregar a: <?= e($pl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> Subir música</button>
                    <div class="form-text mt-2 small">
                        <i class="bi bi-info-circle"></i> Puedes seleccionar <strong>múltiples archivos</strong> a la vez (Ctrl o Shift). Si seleccionas una playlist, las canciones se agregarán automáticamente a ella.
                    </div>
                </form>

                <!-- Acciones masivas -->
                <form method="post" id="bulkForm" action="">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 p-2 border rounded bg-body-tertiary">
                        <div class="form-check ms-2">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label small fw-bold" for="selectAll">Marcar todas</label>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <select name="playlist_id" id="bulkPlaylistSelect" class="form-select form-select-sm" style="max-width:180px;">
                                <option value="">— Asignar a playlist —</option>
                                <?php foreach ($playlists as $pl): ?>
                                    <option value="<?= (int) $pl['id'] ?>"><?= e($pl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" onclick="submitBulk('<?= $autodjUrl ?>/bulk-add')" class="btn btn-sm btn-outline-info text-nowrap"><i class="bi bi-plus-circle"></i> Agregar a lista</button>
                            <button type="submit" onclick="submitBulkAll('<?= $autodjUrl ?>/bulk-add')" class="btn btn-sm btn-outline-success text-nowrap"><i class="bi bi-check-all"></i> TODAS a lista</button>
                            <button type="submit" onclick="submitBulkDelete('<?= $autodjUrl ?>/tracks/bulk-delete')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th style="width:30px"></th>
                                    <th>Título / Canción</th>
                                    <th>Artista</th>
                                    <th>Tamaño</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$tracks): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Aún no hay canciones en la biblioteca. Sube tus MP3 para comenzar.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($tracks as $t): ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input track-checkbox" type="checkbox" name="track_ids[]" value="<?= (int) $t['id'] ?>">
                                    </td>
                                    <td class="text-truncate" style="max-width:200px" title="<?= e($t['original_name']) ?>">
                                        <i class="bi bi-music-note text-info"></i> <?= e($t['title'] ?: $t['original_name']) ?>
                                    </td>
                                    <td class="small text-muted text-truncate" style="max-width:120px"><?= e($t['artist'] ?? '—') ?></td>
                                    <td class="small"><?= human_size((int) $t['filesize']) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="deleteSingleTrack(<?= (int) $t['id'] ?>)"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Playlists -->
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-folder-plus"></i> Crear Nueva Playlist</div>
            <div class="card-body">
                <form method="post" action="<?= $autodjUrl ?>/playlists">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="mb-2">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nombre de la playlist (ej: Pop 80s, Salsa, Baladas)" required>
                    </div>
                    <div class="row g-2 align-items-center">
                        <div class="col-6">
                            <input type="number" name="weight" class="form-control form-control-sm" value="1" min="1" title="Peso / Prioridad en la rotación">
                            <div class="form-text small">Peso rotación</div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="shuffle" id="sh" value="1" checked>
                                <label class="form-check-label small" for="sh">Orden aleatorio</label>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i> Crear Playlist</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <h6 class="mb-2 text-muted fw-bold"><i class="bi bi-collection-play"></i> Playlists de la Estación</h6>

        <?php foreach ($playlists as $pl): 
            $items = \App\Models\Playlist::items((int) $pl['id']);
            $isActive = (int) $pl['is_active'] === 1;
        ?>
            <div class="card mb-3 border-<?= $isActive ? 'success' : 'secondary' ?>">
                <div class="card-header d-flex justify-content-between align-items-center bg-body-tertiary py-2">
                    <div>
                        <strong><i class="bi bi-collection-play-fill text-<?= $isActive ? 'success' : 'muted' ?>"></i> <?= e($pl['name']) ?></strong>
                        <?php if ($isActive): ?>
                            <span class="badge bg-success ms-1"><i class="bi bi-broadcast"></i> AL AIRE</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-1">INACTIVA</span>
                        <?php endif; ?>
                        <span class="badge bg-dark-subtle text-body border ms-1"><?= count($items) ?> temas</span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <!-- Boton Activar/Desactivar -->
                        <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/toggle" class="d-inline">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-<?= $isActive ? 'outline-warning' : 'outline-success' ?> py-0" title="<?= $isActive ? 'Desactivar playlist' : 'Activar playlist' ?>">
                                <?= $isActive ? '<i class="bi bi-pause-fill"></i> Pause' : '<i class="bi bi-check-lg"></i> Activar' ?>
                            </button>
                        </form>
                        <!-- Eliminar Playlist -->
                        <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/delete" class="d-inline" onsubmit="return confirm('¿Eliminar la playlist <?= e($pl['name']) ?>?')">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger py-0" title="Eliminar playlist"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Boton de reproduccion directa al aire -->
                    <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/play" class="mb-3">
                        <?= \App\Core\Csrf::field() ?>
                        <button class="btn btn-sm btn-success w-100 shadow-sm"><i class="bi bi-play-circle-fill"></i> Enviar esta Lista al Aire Ahora</button>
                    </form>

                    <!-- Agregar pista individual -->
                    <?php if ($tracks): ?>
                    <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/tracks" class="input-group input-group-sm mb-2">
                        <?= \App\Core\Csrf::field() ?>
                        <select name="track_id" class="form-select">
                            <option value="">— Elegir canción —</option>
                            <?php foreach ($tracks as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= e($t['title'] ?: $t['original_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-info"><i class="bi bi-plus-lg"></i></button>
                    </form>
                    <?php endif; ?>

                    <!-- Items de la playlist -->
                    <?php if (!$items): ?>
                        <p class="small text-muted mb-0 fst-italic">Playlist vacía. Selecciona canciones a la izquierda y presiona "Agregar a lista".</p>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small text-muted fw-bold">Pistas asignadas:</span>
                            <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/clear" class="d-inline" onsubmit="return confirm('¿Vaciar la playlist <?= e($pl['name']) ?>?')">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="btn btn-link text-danger p-0 small text-decoration-none"><i class="bi bi-x-circle"></i> Vaciar lista</button>
                            </form>
                        </div>
                        <ol class="small mb-0 ps-3" style="max-height:220px; overflow-y:auto;">
                        <?php foreach ($items as $it): ?>
                            <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary-subtle">
                                <span class="text-truncate" style="max-width:180px" title="<?= e($it['title'] ?: $it['original_name']) ?>">
                                    <?= e($it['title'] ?: $it['original_name']) ?>
                                </span>
                                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= (int) $pl['id'] ?>/items/<?= (int) $it['item_id'] ?>/remove">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-link text-danger p-0 ms-1"><i class="bi bi-x-lg"></i></button>
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

<!-- Form oculto para eliminar 1 pista individual -->
<form id="singleDeleteForm" method="post" action="" style="display:none;">
    <?= \App\Core\Csrf::field() ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.track-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }
});

function submitBulk(actionUrl) {
    const sel = document.getElementById('bulkPlaylistSelect');
    if (!sel.value) {
        alert('Por favor selecciona una playlist de la lista desplegable.');
        event.preventDefault();
        return;
    }
    const checked = document.querySelectorAll('.track-checkbox:checked');
    if (checked.length === 0) {
        alert('Por favor marca al menos una canción de la biblioteca.');
        event.preventDefault();
        return;
    }
    const form = document.getElementById('bulkForm');
    form.action = actionUrl;
}

function submitBulkAll(actionUrl) {
    const sel = document.getElementById('bulkPlaylistSelect');
    if (!sel.value) {
        alert('Por favor selecciona una playlist a la que deseas asignar TODAS las canciones.');
        event.preventDefault();
        return;
    }
    if (!confirm('¿Agregar TODAS las canciones de la biblioteca a esta playlist?')) {
        event.preventDefault();
        return;
    }
    const form = document.getElementById('bulkForm');
    form.action = actionUrl;
    
    // Add add_all hidden input
    let input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'add_all';
    input.value = '1';
    form.appendChild(input);
}

function submitBulkDelete(actionUrl) {
    const checked = document.querySelectorAll('.track-checkbox:checked');
    if (checked.length === 0) {
        alert('Por favor marca las canciones que deseas eliminar.');
        event.preventDefault();
        return;
    }
    if (!confirm('¿Eliminar las ' + checked.length + ' canciones seleccionadas de la biblioteca?')) {
        event.preventDefault();
        return;
    }
    const form = document.getElementById('bulkForm');
    form.action = actionUrl;
}

function deleteSingleTrack(tid) {
    if (!confirm('¿Eliminar esta canción?')) return;
    const form = document.getElementById('singleDeleteForm');
    form.action = '<?= $autodjUrl ?>/tracks/' + tid + '/delete';
    form.submit();
}
</script>
