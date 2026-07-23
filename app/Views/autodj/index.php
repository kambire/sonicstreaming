<?php
/** @var array $station; @var string $base; @var array $tracks; @var array $playlists; @var int $usageBytes; @var int $quotaMb */
$sid = (int) $station['id'];
$autodjUrl = url($base . '/stations/' . $sid . '/autodj');
$quotaBytes = $quotaMb * 1024 * 1024;
$pct = $quotaBytes > 0 ? min(100, round($usageBytes / $quotaBytes * 100)) : 0;
$running = ($station['autodj_status'] ?? 'stopped') === 'running';
$tab = $_GET['tab'] ?? 'music';

// Filtrar playlists por tipo
$musicPlaylists = array_values(array_filter($playlists, fn($p) => in_array($p['type'] ?? 'general', ['general', 'scheduled'])));
$jinglePlaylists = array_values(array_filter($playlists, fn($p) => ($p['type'] ?? '') === 'jingle'));
$commercialPlaylists = array_values(array_filter($playlists, fn($p) => ($p['type'] ?? '') === 'commercial'));
$exactTimePlaylists = array_values(array_filter($playlists, fn($p) => ($p['type'] ?? '') === 'top_of_hour'));
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <a href="<?= url($base . '/stations/' . $sid) ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Volver a la estación</a>
        <h5 class="mb-0 mt-1"><i class="bi bi-music-note-list text-info"></i> Gestión de AutoDJ y Automatización · <?= e($station['name']) ?>
            <?= $running ? '<span class="badge bg-success"><i class="bi bi-broadcast"></i> Transmitiendo al aire</span>' : '<span class="badge bg-secondary">Detenido</span>' ?>
        </h5>
    </div>
    <div class="d-flex align-items-center flex-wrap gap-2">
        <?php
        $curBitrate = ((int) ($station['bitrate'] ?? 0)) ?: (int) $station['max_bitrate'];
        $maxBitrate = (int) $station['max_bitrate'];
        $bitrateOptions = array_values(array_filter([32, 64, 96, 128, 160, 192, 256, 320], fn($b) => $b <= $maxBitrate));
        if (!in_array($curBitrate, $bitrateOptions, true)) { $bitrateOptions[] = $curBitrate; sort($bitrateOptions); }
        ?>
        <form method="post" action="<?= $autodjUrl ?>/bitrate" class="d-flex align-items-center gap-1" title="Calidad de emisión del AutoDJ (bitrate MP3). Tope del plan: <?= $maxBitrate ?> kbps">
            <?= \App\Core\Csrf::field() ?>
            <span class="small text-muted d-none d-md-inline"><i class="bi bi-soundwave text-info"></i> Calidad:</span>
            <select name="bitrate" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <?php foreach ($bitrateOptions as $b): ?>
                    <option value="<?= $b ?>" <?= $b === $curBitrate ? 'selected' : '' ?>><?= $b ?> kbps</option>
                <?php endforeach; ?>
            </select>
        </form>
        <div class="btn-group">
            <?php if ($running): ?>
                <form method="post" action="<?= $autodjUrl ?>/skip"><?= \App\Core\Csrf::field() ?>
                    <button class="btn btn-sm btn-warning" title="Saltar a la siguiente canción al aire"><i class="bi bi-skip-end-fill"></i> Saltar Tema</button></form>
            <?php endif; ?>
            <form method="post" action="<?= $autodjUrl ?>/start"><?= \App\Core\Csrf::field() ?>
                <button class="btn btn-sm btn-success" <?= $running ? 'disabled' : '' ?>><i class="bi bi-play-fill"></i> Iniciar AutoDJ</button></form>
            <form method="post" action="<?= $autodjUrl ?>/stop"><?= \App\Core\Csrf::field() ?>
                <button class="btn btn-sm btn-danger" <?= $running ? '' : 'disabled' ?>><i class="bi bi-stop-fill"></i> Detener</button></form>
        </div>
    </div>
</div>

<!-- MENÚ DE AUTOMATIZACIÓN (DISPONIBLE EN EL MENÚ LATERAL IZQUIERDO Y COMPACTO) -->
<div class="d-flex align-items-center justify-content-between bg-dark bg-opacity-50 p-2 rounded mb-4 border border-secondary border-opacity-25 shadow-sm">
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted small ms-2"><i class="bi bi-layout-sidebar-inset text-info"></i> Sección AutoDJ:</span>
        <?php if ($tab === 'music'): ?>
            <span class="badge bg-primary px-3 py-2"><i class="bi bi-music-note-beamer"></i> 1. Música & Playlists Generales</span>
        <?php elseif ($tab === 'jingles'): ?>
            <span class="badge bg-info text-dark px-3 py-2"><i class="bi bi-mic-fill"></i> 2. Viñetas & Separadores (Cada X temas)</span>
        <?php elseif ($tab === 'commercials'): ?>
            <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-badge-ad-fill"></i> 3. Publicidad Rotativa (Cada X temas)</span>
        <?php elseif ($tab === 'exact_time'): ?>
            <span class="badge bg-danger px-3 py-2"><i class="bi bi-clock-history"></i> 4. Hora Exacta / En Punto</span>
        <?php endif; ?>
    </div>

    <div class="btn-group btn-group-sm me-1">
        <a href="<?= $autodjUrl ?>?tab=music" class="btn btn-outline-secondary text-light <?= ($tab === 'music') ? 'active' : '' ?>" title="Música & Generales"><i class="bi bi-music-note"></i> Música</a>
        <a href="<?= $autodjUrl ?>?tab=jingles" class="btn btn-outline-secondary text-light <?= ($tab === 'jingles') ? 'active' : '' ?>" title="Viñetas"><i class="bi bi-mic"></i> Viñetas (<?= count($jinglePlaylists) ?>)</a>
        <a href="<?= $autodjUrl ?>?tab=commercials" class="btn btn-outline-secondary text-light <?= ($tab === 'commercials') ? 'active' : '' ?>" title="Publicidad"><i class="bi bi-badge-ad"></i> Publicidad (<?= count($commercialPlaylists) ?>)</a>
        <a href="<?= $autodjUrl ?>?tab=exact_time" class="btn btn-outline-secondary text-light <?= ($tab === 'exact_time') ? 'active' : '' ?>" title="Hora Exacta"><i class="bi bi-clock"></i> Hora Exacta (<?= count($exactTimePlaylists) ?>)</a>
    </div>
</div>

<?php if ($tab === 'music'): ?>
<!-- ===================================================================== -->
<!-- PESTAÑA 1: MÚSICA Y PROGRAMACIÓN GENERAL                             -->
<!-- ===================================================================== -->
<div class="row g-3">
    <!-- Biblioteca de música -->
    <div class="col-lg-7">
        <div class="card mb-3 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-disc text-primary"></i> Biblioteca de Música (<?= count($tracks) ?> canciones)</span>
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
                <form method="post" id="uploadForm" action="<?= $autodjUrl ?>/upload" enctype="multipart/form-data" class="mb-4 p-3 border rounded bg-body-tertiary" onsubmit="return handleUploadSubmit(event)">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold mb-0"><i class="bi bi-cloud-upload text-primary"></i> Subir canciones a la biblioteca</label>
                        <span class="badge bg-secondary">Máximo 500 canciones</span>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <input type="file" id="trackInput" name="tracks[]" class="form-control" accept="audio/*" multiple required>
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
                    <button type="submit" id="btnUploadSubmit" class="btn btn-primary btn-sm w-100"><i class="bi bi-upload"></i> Subir archivos de música</button>
                </form>

                <!-- Acciones masivas -->
                <form method="post" id="bulkForm" action="<?= $autodjUrl ?>/bulk-add">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 p-2 border rounded bg-body-tertiary">
                        <div class="form-check ms-2">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label small fw-bold" for="selectAll">Marcar todas</label>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <select name="playlist_id" id="bulkPlaylistSelect" class="form-select form-select-sm" style="max-width:180px;">
                                <option value="">— Asignar a lista —</option>
                                <?php foreach ($playlists as $pl): ?>
                                    <option value="<?= (int) $pl['id'] ?>"><?= e($pl['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" onclick="setFormAction('<?= $autodjUrl ?>/bulk-add')" class="btn btn-sm btn-outline-info text-nowrap"><i class="bi bi-plus-circle"></i> Agregar a lista</button>
                            <button type="submit" onclick="selectAllAndSubmit('<?= $autodjUrl ?>/bulk-add')" class="btn btn-sm btn-outline-success text-nowrap"><i class="bi bi-check-all"></i> TODAS a lista</button>
                            <button type="submit" onclick="setFormAction('<?= $autodjUrl ?>/tracks/bulk-delete')" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
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
                                    <td><input class="form-check-input track-checkbox" type="checkbox" name="track_ids[]" value="<?= (int) $t['id'] ?>"></td>
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

    <!-- Playlists musicales -->
    <div class="col-lg-5">
        <div class="card mb-3 shadow-sm">
            <div class="card-header fw-bold"><i class="bi bi-folder-plus text-primary"></i> Crear Nueva Playlist / Programa</div>
            <div class="card-body">
                <form method="post" action="<?= $autodjUrl ?>/playlists">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="mb-2">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nombre (ej: Mañanas Retro, Salsa 14-18hs)" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Tipo de programación</label>
                            <select name="type" class="form-select form-select-sm" onchange="toggleScheduleFields(this, 'createTimeFields')">
                                <option value="general">Rotación General (Todo el día)</option>
                                <option value="scheduled">Programada por Horario (Hora inicio/fin)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small mb-1">Prioridad / Peso</label>
                            <input type="number" name="weight" class="form-control form-control-sm" value="1" min="1">
                        </div>
                    </div>
                    <div class="row g-2 mb-2" id="createTimeFields" style="display:none;">
                        <div class="col-6">
                            <label class="form-label small mb-1">Hora Inicio</label>
                            <input type="time" name="start_time" class="form-control form-control-sm" value="14:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1">Hora Fin</label>
                            <input type="time" name="end_time" class="form-control form-control-sm" value="18:00">
                        </div>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="shuffle" id="sh" value="1" checked>
                        <label class="form-check-label small" for="sh">Orden aleatorio (Shuffle)</label>
                    </div>
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus-lg"></i> Crear Playlist / Programa</button>
                </form>
            </div>
        </div>

        <h6 class="mb-2 text-muted fw-bold"><i class="bi bi-collection-play"></i> Listas de Música Activas</h6>
        <?php foreach ($musicPlaylists as $pl): $pid = (int) $pl['id']; $items = \App\Models\Playlist::items($pid); $countItems = count($items); $isActive = (int) $pl['is_active'] === 1; $isScheduled = ($pl['type'] ?? 'general') === 'scheduled'; ?>
            <div class="card mb-3 border-secondary shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-body-tertiary py-2">
                    <div>
                        <strong><i class="bi bi-collection-play-fill text-primary"></i> <?= e($pl['name']) ?></strong>
                        <?php if ($isActive): ?>
                            <?php if ($isScheduled): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock-history"></i> Programada (<?= e(substr($pl['start_time'] ?? '00:00', 0, 5)) ?> - <?= e(substr($pl['end_time'] ?? '23:59', 0, 5)) ?>)</span>
                            <?php else: ?>
                                <span class="badge bg-success"><i class="bi bi-broadcast"></i> AL AIRE</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-secondary">INACTIVA</span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/toggle" class="d-inline">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-<?= $isActive ? 'outline-warning' : 'outline-success' ?> py-0"><?= $isActive ? 'Pausar' : 'Activar' ?></button>
                        </form>
                        <a href="<?= $autodjUrl ?>/playlists/<?= $pid ?>" class="btn btn-sm btn-info py-0"><i class="bi bi-music-note"></i> Ver archivos (<?= $countItems ?>)</a>
                        <button class="btn btn-sm btn-outline-secondary py-0" data-bs-toggle="modal" data-bs-target="#editModal<?= $pid ?>"><i class="bi bi-gear"></i></button>
                        <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/delete" class="d-inline" onsubmit="return confirm('¿Eliminar esta playlist?')">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-2 small text-muted">
                    Archivos en lista: <strong><?= $countItems ?></strong> · Orden: <strong><?= (int) $pl['shuffle'] === 1 ? 'Aleatorio (Shuffle)' : 'Secuencial' ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php elseif ($tab === 'jingles'): ?>
<!-- ===================================================================== -->
<!-- PESTAÑA 2: VIÑETAS Y SEPARADORES POR X CANCIONES                      -->
<!-- ===================================================================== -->
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-info shadow-sm">
            <div class="card-header bg-info-subtle text-info-emphasis fw-bold">
                <i class="bi bi-mic-fill text-info"></i> Crear Lista de Viñetas / Separadores
            </div>
            <div class="card-body">
                <form method="post" action="<?= $autodjUrl ?>/playlists">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="type" value="jingle">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre de la lista de viñetas</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Ej: Viñetas Estación FM, IDs Institucionales" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-info"><i class="bi bi-arrow-repeat"></i> Frecuencia de emisión</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Reproducir 1 viñeta cada</span>
                            <input type="number" name="play_every_x" class="form-control" value="3" min="1" max="50" required>
                            <span class="input-group-text">canciones</span>
                        </div>
                        <div class="form-text small">Ejemplo: Con valor 3, sonarán 3 canciones de música y luego 1 viñeta de esta lista.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="shuffle" id="shJingle" value="1" checked>
                        <label class="form-check-label small" for="shJingle">Orden aleatorio entre viñetas</label>
                    </div>
                    <button class="btn btn-info btn-sm w-100 fw-bold text-dark"><i class="bi bi-plus-circle"></i> Crear Lista de Viñetas</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-collection-play-fill text-info"></i> Listas de Viñetas y Separadores Configuradas</span>
                <span class="badge bg-info text-dark"><?= count($jinglePlaylists) ?> configuradas</span>
            </div>
            <div class="card-body">
                <?php if (!$jinglePlaylists): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-mic fs-1 text-secondary d-block mb-2"></i>
                        Aún no has creado ninguna lista de viñetas o separadores.<br>
                        Usa el formulario de la izquierda para configurar tu primera viñeta cada X canciones.
                    </div>
                <?php endif; ?>
                <?php foreach ($jinglePlaylists as $pl): $pid = (int) $pl['id']; $items = \App\Models\Playlist::items($pid); $countItems = count($items); $isActive = (int) $pl['is_active'] === 1; ?>
                    <div class="card mb-3 border-info shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center bg-body-tertiary py-2">
                            <div>
                                <strong><i class="bi bi-mic-fill text-info"></i> <?= e($pl['name']) ?></strong>
                                <span class="badge bg-info text-dark">1 viñeta cada <?= (int)($pl['play_every_x'] ?? 3) ?> temas</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/toggle" class="d-inline">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-<?= $isActive ? 'outline-warning' : 'outline-success' ?> py-0"><?= $isActive ? 'Pausar' : 'Activar' ?></button>
                                </form>
                                <a href="<?= $autodjUrl ?>/playlists/<?= $pid ?>" class="btn btn-sm btn-info py-0"><i class="bi bi-music-note"></i> Cargar audios (<?= $countItems ?>)</a>
                                <button class="btn btn-sm btn-outline-secondary py-0" data-bs-toggle="modal" data-bs-target="#editModal<?= $pid ?>"><i class="bi bi-gear"></i></button>
                                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/delete" class="d-inline" onsubmit="return confirm('¿Eliminar esta playlist?')">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-2 small text-muted">
                            Archivos cargados: <strong><?= $countItems ?></strong> · Frecuencia: <strong>1 viñeta cada <?= (int)($pl['play_every_x'] ?? 3) ?> canciones de música</strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'commercials'): ?>
<!-- ===================================================================== -->
<!-- PESTAÑA 3: PUBLICIDAD ROTATIVA / COMERCIALES                           -->
<!-- ===================================================================== -->
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-warning shadow-sm">
            <div class="card-header bg-warning-subtle text-warning-emphasis fw-bold">
                <i class="bi bi-badge-ad-fill text-warning"></i> Crear Lista de Publicidad Rotativa
            </div>
            <div class="card-body">
                <form method="post" action="<?= $autodjUrl ?>/playlists">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="type" value="commercial">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre del paquete de publicidad</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Ej: Pauta Auspiciantes Julio, Spots Comerciales" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-warning"><i class="bi bi-arrow-repeat"></i> Frecuencia de emisión</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Intercalar 1 publicidad cada</span>
                            <input type="number" name="play_every_x" class="form-control" value="5" min="1" max="100" required>
                            <span class="input-group-text">canciones</span>
                        </div>
                        <div class="form-text small">Ejemplo: Con valor 5, sonarán 5 canciones musicales y luego 1 pauta comercial de esta lista.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="shuffle" id="shComm" value="1" checked>
                        <label class="form-check-label small" for="shComm">Orden aleatorio entre comerciales</label>
                    </div>
                    <button class="btn btn-warning btn-sm w-100 fw-bold text-dark"><i class="bi bi-plus-circle"></i> Crear Lista de Publicidad</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-collection-play-fill text-warning"></i> Paquetes de Publicidad Rotativa</span>
                <span class="badge bg-warning text-dark"><?= count($commercialPlaylists) ?> configuradas</span>
            </div>
            <div class="card-body">
                <?php if (!$commercialPlaylists): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-megaphone fs-1 text-secondary d-block mb-2"></i>
                        No hay pautas de publicidad rotativa creadas.<br>
                        Usa el formulario para programar comerciales intercalados en la música.
                    </div>
                <?php endif; ?>
                <?php foreach ($commercialPlaylists as $pl): $pid = (int) $pl['id']; $items = \App\Models\Playlist::items($pid); $countItems = count($items); $isActive = (int) $pl['is_active'] === 1; ?>
                    <div class="card mb-3 border-warning shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center bg-body-tertiary py-2">
                            <div>
                                <strong><i class="bi bi-badge-ad-fill text-warning"></i> <?= e($pl['name']) ?></strong>
                                <span class="badge bg-warning text-dark">1 spot cada <?= (int)($pl['play_every_x'] ?? 5) ?> temas</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/toggle" class="d-inline">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-<?= $isActive ? 'outline-warning' : 'outline-success' ?> py-0"><?= $isActive ? 'Pausar' : 'Activar' ?></button>
                                </form>
                                <a href="<?= $autodjUrl ?>/playlists/<?= $pid ?>" class="btn btn-sm btn-warning py-0 text-dark"><i class="bi bi-music-note"></i> Cargar spots (<?= $countItems ?>)</a>
                                <button class="btn btn-sm btn-outline-secondary py-0" data-bs-toggle="modal" data-bs-target="#editModal<?= $pid ?>"><i class="bi bi-gear"></i></button>
                                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/delete" class="d-inline" onsubmit="return confirm('¿Eliminar esta playlist?')">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-2 small text-muted">
                            Spots cargados: <strong><?= $countItems ?></strong> · Frecuencia: <strong>1 pauta cada <?= (int)($pl['play_every_x'] ?? 5) ?> canciones de música</strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($tab === 'exact_time'): ?>
<!-- ===================================================================== -->
<!-- PESTAÑA 4: HORA EXACTA / EN PUNTO (INTERRUPCIÓN INMEDIATA)             -->
<!-- ===================================================================== -->
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-danger shadow-sm">
            <div class="card-header bg-danger-subtle text-danger-emphasis fw-bold">
                <i class="bi bi-clock-history text-danger"></i> Programar Anuncio a Hora Exacta / En Punto
            </div>
            <div class="card-body">
                <form method="post" action="<?= $autodjUrl ?>/playlists">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="type" value="top_of_hour">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre del anuncio o pauta exacta</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Ej: Hora Hablada En Punto (00m), Cadena de Noticias" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-danger"><i class="bi bi-alarm-fill"></i> Minuto de disparo exacto</label>
                        <select name="cron_minute" class="form-select form-select-sm">
                            <option value="0">00 (Cada hora en punto XX:00)</option>
                            <option value="15">15 (A los 15 minutos XX:15)</option>
                            <option value="30">30 (A la media hora XX:30)</option>
                            <option value="45">45 (A los 45 minutos XX:45)</option>
                        </select>
                    </div>
                    <div class="p-2 border rounded border-danger bg-danger-subtle mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="interrupt_immediately" id="intImm" value="1" checked>
                            <label class="form-check-label fw-bold text-danger small" for="intImm">
                                <i class="bi bi-lightning-fill"></i> Interrupción inmediata (Corta la música sonando)
                            </label>
                        </div>
                        <div class="form-text small text-white-50 mt-1">
                            Si está marcado, atenúa y silenciará de inmediato cualquier tema musical al llegar al minuto exacto.
                        </div>
                    </div>
                    <button class="btn btn-danger btn-sm w-100 fw-bold"><i class="bi bi-plus-circle"></i> Programar Hora Exacta</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-collection-play-fill text-danger"></i> Anuncios y Pautas a Hora Exacta</span>
                <span class="badge bg-danger"><?= count($exactTimePlaylists) ?> configuradas</span>
            </div>
            <div class="card-body">
                <?php if (!$exactTimePlaylists): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-clock fs-1 text-secondary d-block mb-2"></i>
                        Aún no tienes pautas a la hora exacta o en punto.<br>
                        Usa el formulario para programar la hora hablada o spots que cortan la música a las XX:00 en punto.
                    </div>
                <?php endif; ?>
                <?php foreach ($exactTimePlaylists as $pl): $pid = (int) $pl['id']; $items = \App\Models\Playlist::items($pid); $countItems = count($items); $isActive = (int) $pl['is_active'] === 1; ?>
                    <div class="card mb-3 border-danger shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center bg-body-tertiary py-2">
                            <div>
                                <strong><i class="bi bi-clock-fill text-danger"></i> <?= e($pl['name']) ?></strong>
                                <span class="badge bg-danger">Minuto <?= (int)($pl['cron_minute'] ?? 0) ?>m0s</span>
                                <?php if (!empty($pl['interrupt_immediately'])): ?><span class="badge bg-dark border text-light"><i class="bi bi-lightning-fill text-warning"></i> Corte Inmediato</span><?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/toggle" class="d-inline">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-<?= $isActive ? 'outline-warning' : 'outline-success' ?> py-0"><?= $isActive ? 'Pausar' : 'Activar' ?></button>
                                </form>
                                <a href="<?= $autodjUrl ?>/playlists/<?= $pid ?>" class="btn btn-sm btn-danger py-0"><i class="bi bi-music-note"></i> Cargar audios (<?= $countItems ?>)</a>
                                <button class="btn btn-sm btn-outline-secondary py-0" data-bs-toggle="modal" data-bs-target="#editModal<?= $pid ?>"><i class="bi bi-gear"></i></button>
                                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/delete" class="d-inline" onsubmit="return confirm('¿Eliminar esta playlist?')">
                                    <?= \App\Core\Csrf::field() ?>
                                    <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-2 small text-muted">
                            Audios cargados: <strong><?= $countItems ?></strong> · Minuto de disparo: <strong>XX:<?= sprintf('%02d', (int)($pl['cron_minute'] ?? 0)) ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- MODALES DE EDICIÓN FUERA DE CONTENEDORES Y NIDIFICADOS DE FORMS -->
<?php foreach ($playlists as $pl): $pid = (int) $pl['id']; $type = $pl['type'] ?? 'general'; ?>
    <div class="modal fade" id="editModal<?= $pid ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="<?= $autodjUrl ?>/playlists/<?= $pid ?>/update">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Lista: <?= e($pl['name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nombre de la lista</label>
                            <input type="text" name="name" class="form-control" value="<?= e($pl['name']) ?>" required>
                        </div>
                        <?php if ($type === 'jingle' || $type === 'commercial'): ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Frecuencia (1 pista cada X temas)</label>
                                <input type="number" name="play_every_x" class="form-control" value="<?= (int)($pl['play_every_x'] ?? 3) ?>" min="1">
                            </div>
                        <?php elseif ($type === 'top_of_hour'): ?>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Minuto de disparo exacto (0-59)</label>
                                <input type="number" name="cron_minute" class="form-control" value="<?= (int)($pl['cron_minute'] ?? 0) ?>" min="0" max="59">
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="interrupt_immediately" id="intImm<?= $pid ?>" value="1" <?= !empty($pl['interrupt_immediately']) ? 'checked' : '' ?>>
                                <label class="form-check-label small fw-bold text-danger" for="intImm<?= $pid ?>">Interrupción inmediata de música al instante</label>
                            </div>
                        <?php endif; ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="shuffle" id="sh<?= $pid ?>" value="1" <?= (int)$pl['shuffle'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="sh<?= $pid ?>">Reproducción aleatoria (Shuffle)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- OVERLAY DE CARGA E INDEXACIÓN AL SUBIR MÚSICA -->
<div id="uploadOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" style="background: rgba(13, 17, 26, 0.92); backdrop-filter: blur(8px); z-index: 99999;">
    <div class="card bg-dark border-info text-center p-4 shadow-lg text-white" style="max-width: 460px; border-radius: 16px;">
        <div class="mb-3 position-relative d-inline-block">
            <div class="spinner-border text-info" style="width: 4.5rem; height: 4.5rem;" role="status"></div>
            <i class="bi bi-disc-fill text-info position-absolute top-50 start-50 translate-middle fs-2 animate-spin"></i>
        </div>
        <h5 class="fw-bold text-info mb-1"><i class="bi bi-cloud-upload"></i> Subiendo e Indexando Canciones</h5>
        <p class="small text-white-50 mb-3" id="uploadStatusText">Procesando audios MP3 en la biblioteca AutoDJ...<br>Por favor no cierres ni navegues fuera de esta pantalla.</p>
        <div class="progress bg-secondary bg-opacity-50 mb-2" style="height: 10px; border-radius: 5px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info w-100"></div>
        </div>
        <div class="small text-info-subtle fw-bold" id="uploadCountText">Límite permitido: Máximo 500 canciones.</div>
    </div>
</div>

<style>
@keyframes spinSlow {
    from { transform: translate(-50%, -50%) rotate(0deg); }
    to { transform: translate(-50%, -50%) rotate(360deg); }
}
.animate-spin {
    animation: spinSlow 3s linear infinite;
}
</style>

<script>
const CURRENT_TRACK_COUNT = <?= (int) count($tracks) ?>;
const MAX_ALLOWED_TRACKS = 500;

document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.track-checkbox').forEach(cb => cb.checked = this.checked);
        });
    }
});

function handleUploadSubmit(e) {
    const input = document.getElementById('trackInput');
    if (!input || !input.files || input.files.length === 0) {
        alert('Por favor selecciona al menos un archivo de audio para subir.');
        return false;
    }

    const fileCount = input.files.length;
    if (fileCount > MAX_ALLOWED_TRACKS) {
        alert(`Has seleccionado ${fileCount} archivos. El límite máximo permitido es de 500 canciones.`);
        return false;
    }

    if ((CURRENT_TRACK_COUNT + fileCount) > MAX_ALLOWED_TRACKS) {
        const remaining = MAX_ALLOWED_TRACKS - CURRENT_TRACK_COUNT;
        if (remaining <= 0) {
            alert('Has alcanzado el límite máximo de 500 canciones en la estación. Elimina canciones para poder subir nuevas.');
            return false;
        }
        if (!confirm(`La estación ya tiene ${CURRENT_TRACK_COUNT} canciones. Solo se aceptarán las primeras ${remaining} canciones para no superar el límite de 500. ¿Deseas continuar?`)) {
            return false;
        }
    }

    // Mostrar overlay animado de carga
    const overlay = document.getElementById('uploadOverlay');
    const statusText = document.getElementById('uploadStatusText');
    const countText = document.getElementById('uploadCountText');

    if (statusText) {
        statusText.innerHTML = `Subiendo y procesando <strong>${fileCount}</strong> archivo(s) de audio...<br>Por favor no cierres la ventana.`;
    }
    if (countText) {
        countText.innerText = `Total tras subida: ${Math.min(MAX_ALLOWED_TRACKS, CURRENT_TRACK_COUNT + fileCount)} / 500 canciones.`;
    }
    if (overlay) {
        overlay.classList.remove('d-none');
        overlay.classList.add('d-flex');
    }

    return true;
}

function toggleScheduleFields(select, targetId) {
    const target = document.getElementById(targetId);
    if (target) {
        target.style.display = (select.value === 'scheduled') ? 'flex' : 'none';
    }
}

function setFormAction(url) {
    const form = document.getElementById('bulkForm');
    const plSelect = document.getElementById('bulkPlaylistSelect');
    if (!plSelect.value && url.includes('bulk-add')) {
        alert('Por favor selecciona una playlist destino.');
        return;
    }
    form.action = url;
}

function selectAllAndSubmit(url) {
    const form = document.getElementById('bulkForm');
    const plSelect = document.getElementById('bulkPlaylistSelect');
    if (!plSelect.value) {
        alert('Por favor selecciona una playlist destino.');
        return;
    }
    document.querySelectorAll('.track-checkbox').forEach(cb => cb.checked = true);
    form.action = url;
}

function deleteSingleTrack(tid) {
    if (!confirm('¿Eliminar esta canción permanentemente?')) return;
    const form = document.createElement('form');
    form.method = 'post';
    form.action = '<?= $autodjUrl ?>/tracks/' + tid + '/delete';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = '<?= \App\Core\Csrf::token() ?>';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}
</script>
