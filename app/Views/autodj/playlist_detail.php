<?php
/** @var array $station; @var string $base; @var array $playlist; @var array $items; @var array $allTracks */
$sid = (int) $station['id'];
$pid = (int) $playlist['id'];
$autodjUrl = url($base . '/stations/' . $sid . '/autodj');
$isActive = (int) $playlist['is_active'] === 1;
$isScheduled = ($playlist['type'] ?? 'general') === 'scheduled';
$totalSize = array_sum(array_column($items, 'filesize'));
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <a href="<?= $autodjUrl ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Volver a AutoDJ</a>
        <h5 class="mb-0 mt-1">
            <i class="bi bi-collection-play-fill text-info"></i> Playlist: <?= e($playlist['name']) ?>
            <?php if ($isActive): ?>
                <?php if ($isScheduled): ?>
                    <span class="badge bg-warning text-dark"><i class="bi bi-clock-history"></i> Programada (<?= e(substr($playlist['start_time'] ?? '00:00', 0, 5)) ?> - <?= e(substr($playlist['end_time'] ?? '23:59', 0, 5)) ?>)</span>
                <?php else: ?>
                    <span class="badge bg-success"><i class="bi bi-broadcast"></i> AL AIRE</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge bg-secondary">INACTIVA</span>
            <?php endif; ?>
        </h5>
        <div class="small text-muted mt-1">
            Total: <strong><?= count($items) ?> canciones</strong> (<?= human_size($totalSize) ?>)
        </div>
    </div>
    <div class="btn-group">
        <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/play"><?= \App\Core\Csrf::field() ?>
            <button class="btn btn-sm btn-success"><i class="bi bi-play-circle-fill"></i> Reproducir / Al Aire Ahora</button></form>
        <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/clear" onsubmit="return confirm('¿Vaciar todas las canciones de la playlist <?= e($playlist['name']) ?>?')"><?= \App\Core\Csrf::field() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Vaciar Lista</button></form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-body-tertiary fw-bold"><i class="bi bi-plus-circle"></i> Agregar canciones desde la biblioteca</div>
    <div class="card-body">
        <?php if (!$allTracks): ?>
            <p class="small text-muted mb-0">No hay canciones en la biblioteca de la estación. Sube música primero en la pantalla principal del AutoDJ.</p>
        <?php else: ?>
            <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/tracks" class="row g-2 align-items-center">
                <?= \App\Core\Csrf::field() ?>
                <div class="col-md-9">
                    <select name="track_id" class="form-select form-select-sm" required>
                        <option value="">— Seleccionar canción de la biblioteca —</option>
                        <?php foreach ($allTracks as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"><?= e($t['artist'] ? ($t['artist'] . ' - ' . $t['title']) : ($t['title'] ?: $t['original_name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i> Agregar a Playlist</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-music-note-list"></i> Administrador de Canciones (<?= count($items) ?> temas)</span>
        <input type="text" id="trackSearch" class="form-control form-control-sm" style="max-width:240px;" placeholder="Filtrar por nombre o artista...">
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="playlistTable">
                <thead class="table-dark">
                    <tr>
                        <th style="width:50px" class="text-center">#</th>
                        <th>Título / Canción</th>
                        <th>Artista</th>
                        <th style="width:180px">Reproducir / Preescuchar</th>
                        <th style="width:100px">Tamaño</th>
                        <th style="width:70px" class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Esta playlist no tiene canciones asignadas aún.</td></tr>
                <?php endif; ?>
                <?php $idx = 1; foreach ($items as $it): ?>
                    <tr class="track-row">
                        <td class="text-center text-muted fw-bold"><?= $idx++ ?></td>
                        <td class="track-title fw-semibold">
                            <i class="bi bi-music-note-beamer text-info me-1"></i> <?= e($it['title'] ?: $it['original_name']) ?>
                        </td>
                        <td class="track-artist small text-muted"><?= e($it['artist'] ?? '—') ?></td>
                        <td>
                            <audio controls preload="none" style="height:32px; width:170px;" src="<?= $autodjUrl ?>/tracks/<?= (int) $it['id'] ?>/stream"></audio>
                        </td>
                        <td class="small"><?= human_size((int) $it['filesize']) ?></td>
                        <td class="text-center">
                            <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/items/<?= (int) $it['item_id'] ?>/remove" onsubmit="return confirm('¿Quitar esta canción de la playlist?')">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-danger py-0" title="Quitar de playlist"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('trackSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('#playlistTable tbody tr.track-row');
            rows.forEach(row => {
                const title = row.querySelector('.track-title').textContent.toLowerCase();
                const artist = row.querySelector('.track-artist').textContent.toLowerCase();
                if (title.includes(query) || artist.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
