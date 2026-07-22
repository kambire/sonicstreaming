<?php
/**
 * Detalle de estación reutilizable con interfaz moderna y minimalista.
 * Vars: $station (con hostname/driver via JOIN), $base ('admin'|'client'|'reseller'),
 *       $latest (ultimo snapshot|null), $adminPass (string|null), $showSensitive (bool),
 *       $owner (array|null)
 */
$base = $base ?? 'admin';
$showSensitive = $showSensitive ?? true;
$host = $station['hostname'] ?? '127.0.0.1';
$port = (int) $station['port'];
$djPort = (int) ($station['dj_port'] ?? ($port + 10000));
$reqHost = $_SERVER['HTTP_HOST'] ?? $host;
$domain = parse_url('https://' . $reqHost, PHP_URL_HOST) ?: $host;
$webPort = parse_url('https://' . $reqHost, PHP_URL_PORT) ?: 7000;

$streamUrlSsl  = "https://{$domain}:{$webPort}/listen/{$port}/stream";
$streamUrlHttp = "http://{$domain}:{$port}/stream";
$adminUrlSsl   = "https://{$domain}:{$webPort}/listen/{$port}/admin.cgi";
$sid = (int) $station['id'];
$running = ($station['status'] ?? '') === 'running';
$autodjRunning = ($station['autodj_status'] ?? 'stopped') === 'running';
?>

<!-- CABECERA Y ACCIONES RÁPIDAS -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2 pb-3 border-bottom border-secondary border-opacity-25">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h4 class="mb-0 fw-bold text-white"><i class="bi bi-broadcast-pin text-info me-2"></i><?= e($station['name']) ?></h4>
            <?= status_badge($station['status']) ?>
        </div>
        <?php if (!empty($owner)): ?><div class="small text-muted"><i class="bi bi-person me-1"></i> Cliente Propietario: <strong class="text-light"><?= e($owner['name']) ?></strong></div><?php endif; ?>
    </div>
    
    <div class="btn-toolbar gap-2">
        <button type="button" class="btn btn-sm btn-danger fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#micStudioModal">
            <i class="bi bi-mic-fill me-1"></i> 🎙️ Hablar en Vivo
        </button>

        <?php if ($station['autodj_enabled'] ?? 0): ?>
            <a href="<?= url($base . '/stations/' . $sid . '/autodj') ?>" class="btn btn-sm btn-info fw-bold text-dark shadow-sm">
                <i class="bi bi-music-note-list"></i> AutoDJ & Playlists
            </a>
        <?php endif; ?>
        
        <a href="<?= url($base . '/stations/' . $sid . '/analytics') ?>" class="btn btn-sm btn-outline-info fw-bold shadow-sm">
            <i class="bi bi-globe-americas"></i> Analíticas & Mapa
        </a>
        
        <?php if ($base === 'admin'): ?>
            <a href="<?= url('admin/stations/' . $sid . '/edit') ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
        <?php endif; ?>

        <div class="btn-group shadow-sm">
            <form method="post" action="<?= url($base . '/stations/' . $sid . '/start') ?>" class="d-inline">
                <?= \App\Core\Csrf::field() ?>
                <button class="btn btn-sm btn-success" <?= $running ? 'disabled' : '' ?>><i class="bi bi-play-fill"></i> Iniciar</button>
            </form>
            <form method="post" action="<?= url($base . '/stations/' . $sid . '/restart') ?>" class="d-inline">
                <?= \App\Core\Csrf::field() ?>
                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-clockwise"></i> Reiniciar</button>
            </form>
            <form method="post" action="<?= url($base . '/stations/' . $sid . '/stop') ?>" class="d-inline">
                <?= \App\Core\Csrf::field() ?>
                <button class="btn btn-sm btn-danger" <?= $running ? '' : 'disabled' ?>><i class="bi bi-stop-fill"></i> Detener</button>
            </form>
        </div>
    </div>
</div>

<!-- REPRODUCTOR WEB DE PREESCUCHA -->
<div class="card mb-4 border-0 shadow-sm text-white overflow-hidden" style="background: linear-gradient(135deg, #141923 0%, #0d111a 100%); border-left: 4px solid #0dcaf0 !important;">
    <div class="card-body p-3 p-md-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 d-flex align-items-center justify-content-center text-info shadow-sm" style="width:58px; height:58px; border: 2px solid rgba(13, 202, 240, 0.25);">
                    <i class="bi bi-disc-fill fs-2"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success bg-gradient text-white px-2 py-1"><i class="bi bi-broadcast"></i> SEÑAL EN VIVO</span>
                    <span class="badge bg-dark border border-secondary text-info"><i class="bi bi-lock-fill text-success"></i> HTTPS SSL</span>
                </div>
                <h6 class="mb-1 text-white fw-bold"><i class="bi bi-music-note-beamer text-info"></i> Preescuchar Emisión Al Aire</h6>
                <div class="small text-white-50 text-truncate" style="max-width:480px;">
                    <i class="bi bi-music-note"></i> Canción en emisión: <strong id="topNowPlaying" class="text-info"><?= e($latest['song_title'] ?? 'Cargando título en vivo...') ?></strong>
                </div>
            </div>
            <div class="col-12 col-md-auto">
                <div class="p-2 rounded bg-black bg-opacity-60 border border-secondary shadow-sm">
                    <audio id="mainAudioPlayer" controls preload="none" style="min-width:280px; height:38px;" src="<?= e($streamUrlSsl) ?>"></audio>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GRID PRINCIPAL: ESTADO EN VIVO Y GRÁFICO HISTÓRICO -->
<div class="row g-3 mb-4">
    <!-- Resumen en Vivo (4 columnas) -->
    <div class="col-lg-4">
        <div class="card h-100 shadow-sm border-0 bg-dark bg-opacity-50">
            <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 fw-bold text-white">
                <i class="bi bi-activity text-info me-1"></i> Estado & Audiencia en Tiempo Real
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded bg-body-tertiary">
                    <span class="text-muted small"><i class="bi bi-people-fill text-info me-1"></i> Oyentes Activos</span>
                    <span class="fs-3 fw-bold text-info" id="liveListeners"><?= (int) ($latest['current_listeners'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="text-muted small"><i class="bi bi-graph-up-arrow text-warning me-1"></i> Pico Máximo</span>
                    <span class="fw-bold text-light" id="peakListeners"><?= (int) ($latest['peak_listeners'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="text-muted small"><i class="bi bi-signal me-1"></i> Transmisión Shoutcast</span>
                    <span id="upState"><?= !empty($latest['is_up']) ? '<span class="badge bg-success">Al aire</span>' : '<span class="badge bg-secondary">Fuera</span>' ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3 px-1">
                    <span class="text-muted small"><i class="bi bi-disc me-1"></i> Bitrate Máximo</span>
                    <span class="fw-bold text-light"><?= (int) $station['max_bitrate'] ?> kbps</span>
                </div>
                <hr class="border-secondary border-opacity-25 my-2">
                <div class="text-muted small mb-1"><i class="bi bi-music-note me-1"></i> Título al aire ahora</div>
                <div id="nowPlaying" class="text-truncate fw-bold text-white small p-2 rounded bg-black bg-opacity-40 border border-secondary border-opacity-25">
                    <?= e($latest['song_title'] ?? '—') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfica de audiencia (8 columnas) -->
    <div class="col-lg-8">
        <div class="card h-100 shadow-sm border-0 bg-dark bg-opacity-50">
            <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 fw-bold text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up text-info me-1"></i> Comportamiento de la Audiencia</span>
                <span class="small text-muted fw-normal">Refresco automático cada 10s</span>
            </div>
            <div class="card-body d-flex align-items-center">
                <canvas id="listenersChart" height="110"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- CENTRO DE CONEXIONES Y DATOS TÉCNICOS (PESTAÑAS ORGANIZADAS) -->
<div class="card shadow-sm border-0 bg-dark bg-opacity-50">
    <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25">
        <ul class="nav nav-pills card-header-pills" id="connectionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold small" id="stream-tab" data-bs-toggle="tab" data-bs-target="#stream-panel" type="button" role="tab">
                    <i class="bi bi-link-45deg text-info me-1"></i> 1. Direcciones URL de Streaming (HTTPS/HTTP)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-light" id="harbor-tab" data-bs-toggle="tab" data-bs-target="#harbor-panel" type="button" role="tab">
                    <i class="bi bi-broadcast text-success me-1"></i> 2. Transmitir en Vivo (VirtualDJ, ZaraRadio, OBS)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-light" id="ftp-tab" data-bs-toggle="tab" data-bs-target="#ftp-panel" type="button" role="tab">
                    <i class="bi bi-folder-symlink text-warning me-1"></i> 3. AutoDJ & Acceso FTP
                </button>
            </li>
        </ul>
    </div>
    
    <div class="card-body">
        <div class="tab-content" id="connectionTabsContent">
            
            <!-- PESTAÑA 1: DIRECCIONES STREAMING -->
            <div class="tab-pane fade show active" id="stream-panel" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-success mb-1">
                            <i class="bi bi-lock-fill"></i> Stream HTTPS Seguro (Para reproductores web SSL, Apps e iOS)
                        </label>
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" class="form-control font-monospace text-success fw-bold bg-dark text-white border-secondary" readonly value="<?= e($streamUrlSsl) ?>" id="streamUrlSslInput">
                            <button class="btn btn-outline-success" onclick="copyInput('streamUrlSslInput', this)" title="Copiar URL HTTPS">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>
                        <div class="form-text small text-muted">Usa este enlace HTTPS para reproductores en sitios web SSL o aplicaciones móviles.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-info mb-1">
                            <i class="bi bi-globe"></i> Stream HTTP Estándar (Puerto directo)
                        </label>
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" class="form-control font-monospace text-info fw-bold bg-dark text-white border-secondary" readonly value="<?= e($streamUrlHttp) ?>" id="streamUrlHttpInput">
                            <button class="btn btn-outline-info" onclick="copyInput('streamUrlHttpInput', this)" title="Copiar URL HTTP">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>
                        <div class="form-text small text-muted">Usa este enlace para reproductores de escritorio tradicionales (Winamp, VLC, TuneIn).</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-warning mb-1">
                            <i class="bi bi-gear-wide-connected"></i> Consola DNAS v2 Admin (HTTPS)
                        </label>
                        <div class="input-group input-group-sm mb-1">
                            <input type="text" class="form-control font-monospace text-warning fw-bold bg-dark text-white border-secondary" readonly value="<?= e($adminUrlSsl) ?>" id="adminUrlSslInput">
                            <a href="<?= e($adminUrlSsl) ?>" target="_blank" class="btn btn-outline-warning" title="Abrir Admin DNAS">
                                <i class="bi bi-box-arrow-up-right"></i> Abrir DNAS
                            </a>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-light mb-1">
                            <i class="bi bi-file-earmark-music"></i> Listas de Reproducción (.M3U / .PLS)
                        </label>
                        <div class="d-flex gap-2">
                            <a href="<?= url('listen/station_' . $station['id'] . '/m3u') ?>" class="btn btn-sm btn-outline-info flex-fill fw-bold" target="_blank">
                                <i class="bi bi-download"></i> Descargar .M3U
                            </a>
                            <a href="<?= url('listen/station_' . $station['id'] . '/pls') ?>" class="btn btn-sm btn-outline-info flex-fill fw-bold" target="_blank">
                                <i class="bi bi-download"></i> Descargar .PLS
                            </a>
                        </div>
                    </div>

                    <?php if (!empty($station['relay_url'])): ?>
                    <div class="col-12 mt-3 border-top border-secondary border-opacity-25 pt-2">
                        <label class="form-label small fw-bold text-success mb-1">
                            <i class="bi bi-arrow-repeat"></i> URL de Re-transmisión Relay (Fuente Externa Configurada)
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace text-success fw-bold bg-dark text-white border-secondary" readonly value="<?= e($station['relay_url']) ?>" id="relayUrlInput">
                            <button class="btn btn-outline-success" onclick="copyInput('relayUrlInput', this)" title="Copiar Relay URL">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PESTAÑA 2: CONEXIÓN EN VIVO HARBOR -->
            <div class="tab-pane fade" id="harbor-panel" role="tabpanel">
                <div class="alert alert-dark p-2 mb-3 border border-secondary text-white-50 small">
                    <i class="bi bi-info-circle text-info me-1"></i> Configura estos parámetros en tu programa de radio (VirtualDJ, ZaraRadio, BUTT, RadioBOSS, OBS, Mixxx) para transmitir en vivo con transición suave sobre el AutoDJ.
                </div>

                <div class="row g-3">
                    <div class="col-lg-7">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 border border-secondary">
                            <tbody>
                                <tr>
                                    <th class="bg-body-tertiary text-muted" style="width:35%;">Servidor / Host</th>
                                    <td><code class="copyable text-info fw-bold me-2"><?= e($domain) ?></code> <span class="small text-muted">(IP: <code><?= e($host) ?></code>)</span></td>
                                </tr>
                                <tr>
                                    <th class="bg-body-tertiary text-muted">Puerto DJ en Vivo</th>
                                    <td><code class="copyable text-success fw-bold me-2"><?= $djPort ?></code> <span class="badge bg-success">Recomendado Harbor</span></td>
                                </tr>
                                <tr>
                                    <th class="bg-body-tertiary text-muted">Punto de Montaje (Mount)</th>
                                    <td><code class="copyable">/stream</code></td>
                                </tr>
                                <tr>
                                    <th class="bg-body-tertiary text-muted">Usuario (Source User)</th>
                                    <td><code class="copyable">source</code> <span class="small text-muted">(o dejar vacío en ZaraRadio/BUTT)</span></td>
                                </tr>
                                <tr>
                                    <th class="bg-body-tertiary text-muted">Contraseña (Source Pass)</th>
                                    <td><code class="copyable text-warning fw-bold"><?= e($station['source_password']) ?></code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-lg-5">
                        <div class="p-3 border rounded border-secondary bg-black bg-opacity-30 h-100">
                            <h6 class="fw-bold text-info mb-2"><i class="bi bi-sliders"></i> Guía rápida por Software:</h6>
                            <ul class="small text-white-50 mb-0 ps-3">
                                <li class="mb-1"><strong>VirtualDJ / RadioBOSS:</strong> Servidor: <code><?= e($domain) ?>:<?= $djPort ?></code> | Pass: <code><?= e($station['source_password']) ?></code></li>
                                <li class="mb-1"><strong>ZaraRadio + BUTT:</strong> Host: <code><?= e($domain) ?></code> | Port: <code><?= $djPort ?></code> | Pass: <code><?= e($station['source_password']) ?></code> | Mount: <code>/stream</code></li>
                                <li><strong>OBS Studio:</strong> Tipo Icecast/Custom | Stream URL: <code>icecast://<?= e($domain) ?>:<?= $djPort ?>/stream</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 3: AUTODJ & ACCESO FTP -->
            <div class="tab-pane fade" id="ftp-panel" role="tabpanel">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-white mb-2"><i class="bi bi-disc text-info"></i> Gestión de AutoDJ y Biblioteca</h6>
                        <p class="small text-muted mb-3">
                            Estado del servicio AutoDJ: 
                            <?= $autodjRunning ? '<span class="badge bg-success">Transmitiendo al aire</span>' : '<span class="badge bg-secondary">Detenido</span>' ?>
                        </p>
                        <?php if ($station['autodj_enabled'] ?? 0): ?>
                            <a href="<?= url($base . '/stations/' . $sid . '/autodj') ?>" class="btn btn-sm btn-info fw-bold text-dark">
                                <i class="bi bi-folder-play"></i> Abrir Gestor de Playlists y Música
                            </a>
                        <?php else: ?>
                            <div class="alert alert-warning p-2 small mb-0">El AutoDJ está deshabilitado para esta estación.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="p-3 border rounded border-secondary bg-black bg-opacity-30">
                            <h6 class="fw-bold text-warning mb-2"><i class="bi bi-folder-symlink"></i> Subida masiva por FTP (Opcional)</h6>
                            <div class="small text-white-50">
                                Servidor FTP: <code><?= e($domain) ?></code><br>
                                Puerto FTP: <code>21</code><br>
                                Usuario / Clave: Mismos datos de acceso a tu cuenta del panel.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sid = <?= $sid ?>;
    const base = window.APP_BASE || '';
    const ctx = document.getElementById('listenersChart');
    let chart;

    function buildChart(labels, data) {
        if (!ctx) return;
        chart = new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: [{
                label: 'Oyentes', data: data, tension: 0.35,
                borderColor: '#0dcaf0', backgroundColor: 'rgba(13, 202, 240, 0.12)', fill: true, pointRadius: 2, pointHoverRadius: 5
            }]},
            options: { responsive: true, plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#a0aec0' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { ticks: { color: '#a0aec0' }, grid: { color: 'rgba(255,255,255,0.05)' } }
                } 
            }
        });
    }

    fetch(base + '/api/stations/' + sid + '/history')
        .then(r => r.json())
        .then(j => buildChart(j.labels || [], j.data || []))
        .catch(() => buildChart([], []));

    // Refresco en vivo cada 10s
    setInterval(function () {
        fetch(base + '/api/stations/' + sid + '/stats')
            .then(r => r.json())
            .then(j => {
                if (document.getElementById('liveListeners')) document.getElementById('liveListeners').textContent = j.current_listeners ?? 0;
                if (document.getElementById('peakListeners')) document.getElementById('peakListeners').textContent = j.peak_listeners ?? 0;
                if (document.getElementById('nowPlaying')) document.getElementById('nowPlaying').textContent = j.song_title || '—';
                if (document.getElementById('topNowPlaying')) document.getElementById('topNowPlaying').textContent = j.song_title || '—';
                if (document.getElementById('upState')) {
                    document.getElementById('upState').innerHTML = j.is_up
                        ? '<span class="badge bg-success">Al aire</span>'
                        : '<span class="badge bg-secondary">Fuera</span>';
                }
                if (chart) {
                    const t = new Date().toLocaleTimeString().slice(0,5);
                    chart.data.labels.push(t);
                    chart.data.datasets[0].data.push(j.current_listeners ?? 0);
                    if (chart.data.labels.length > 60) { chart.data.labels.shift(); chart.data.datasets[0].data.shift(); }
                    chart.update('none');
                }
            }).catch(() => {});
    }, 10000);
});

function copyInput(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input) {
        navigator.clipboard?.writeText(input.value);
        const origHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2"></i> ¡Copiado!';
        btn.classList.replace('btn-outline-success', 'btn-success');
        btn.classList.replace('btn-outline-info', 'btn-info');
        setTimeout(() => {
            btn.innerHTML = origHTML;
            btn.classList.replace('btn-success', 'btn-outline-success');
            btn.classList.replace('btn-info', 'btn-outline-info');
        }, 2000);
    }
}
</script>

<!-- MODAL INTERACTIVO DE CONFIGURACIÓN DE RE-TRANSMISIÓN RELAY -->
<div class="modal fade" id="relayConfigModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-success text-white shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-arrow-repeat me-2"></i> Configuración de Re-transmisión Relay</h5>
            <?php 
                $userRole = auth()['role'] ?? 'client';
                $baseRole = ($userRole === 'admin') ? 'admin' : (($userRole === 'reseller') ? 'reseller' : 'client');
            ?>
            <form method="post" action="<?= url($baseRole . '/stations/' . $station['id'] . '/settings') ?>">
                <?= \App\Core\Csrf::field() ?>
                <div class="modal-body py-4">
                    <p class="small text-white-50 mb-3">
                        Configura el origen para re-transmitir una señal de radio externa en cualquier formato (MP3, AAC, Icecast, Shoutcast, M3U, PLS).
                    </p>

                    <div class="mb-3">
                        <label class="form-label text-light fw-bold">Modo de Operación de la Emisora</label>
                        <select name="type" class="form-select bg-dark text-white border-secondary">
                            <option value="live" <?= ($station['type'] ?? 'live') === 'live' ? 'selected' : '' ?>>📻 Radio Normal (Emisión en vivo / AutoDJ local)</option>
                            <option value="relay" <?= ($station['type'] ?? 'live') === 'relay' ? 'selected' : '' ?>>🔄 Radio Relay (Retransmisión continua de stream externo)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-light fw-bold">URL de Stream Relay Externo (Fuente Originaria)</label>
                        <input type="url" name="relay_url" class="form-control bg-dark text-white border-secondary font-monospace" placeholder="http://servidor-externo.com:8000/stream" value="<?= e($station['relay_url'] ?? '') ?>">
                        <div class="form-text text-muted">Ejemplo: <code>http://radio-remota.com:8000/stream</code> o <code>https://stream.dominio.com/radio.mp3</code>.</div>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-lg me-1"></i> Guardar y Aplicar Relay</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL INTERACTIVO DE MICRÓFONO WEB STUDIO "HABLAR EN VIVO" -->
<div class="modal fade" id="micStudioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-danger text-white shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-mic-fill me-2"></i> Estudio de Micrófono "Hablar en Vivo"</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="small text-white-50 mb-3">
                    Presiona <strong>"Grabar Voz"</strong>, habla a tu micrófono y al presionar <strong>"Detener y Transmitir"</strong> saldrá al aire atenuando automáticamente la música.
                </p>

                <div class="mb-3">
                    <div id="micStatusBadge" class="badge bg-secondary px-3 py-2 fs-6 mb-3">En Espera (Listo para grabar)</div>
                    
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <button type="button" id="recordMicBtn" class="btn btn-danger rounded-circle p-4 shadow-lg border border-3 border-danger-subtle d-flex flex-column align-items-center justify-content-center" style="width: 120px; height: 120px; transition: transform 0.2s;">
                            <i class="bi bi-mic-fill fs-1 mb-1" id="micIcon"></i>
                            <span id="recordBtnLabel" class="fw-bold" style="font-size: 11px;">GRABAR</span>
                        </button>
                    </div>
                    
                    <div id="recordingTimer" class="font-monospace fs-3 fw-bold text-danger d-none">00:00</div>
                    
                    <!-- VUMETER DE NIVEL DE AUDIO -->
                    <div class="progress bg-secondary bg-opacity-30 mt-3 mx-auto" style="height: 10px; max-width: 300px; border-radius: 5px;">
                        <div id="vumeterBar" class="progress-bar bg-danger transition-none" style="width: 0%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let mediaRecorder;
    let audioChunks = [];
    let audioContext, analyser, microphone, javascriptNode;
    let startTime, timerInterval;

    const BROADCAST_MIC_URL = '<?= url($base . '/stations/' . $sid . '/autodj/broadcast-mic') ?>';
    const CSRF_TOKEN = '<?= \App\Core\Csrf::token() ?>';

    const recBtn = document.getElementById('recordMicBtn');
    const recLabel = document.getElementById('recordBtnLabel');
    const micStatus = document.getElementById('micStatusBadge');
    const timerEl = document.getElementById('recordingTimer');
    const vumeter = document.getElementById('vumeterBar');

    if (recBtn) {
        recBtn.addEventListener('click', async () => {
            if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({
                        audio: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: true,
                            channelCount: 1,
                            sampleRate: 48000
                        }
                    });

                    let options = { mimeType: 'audio/webm;codecs=opus', audioBitsPerSecond: 128000 };
                    if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                        options = { mimeType: 'audio/webm', audioBitsPerSecond: 128000 };
                    }
                    mediaRecorder = new MediaRecorder(stream, options);
                    audioChunks = [];

                    mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                    
                    // Al detener la grabación, se transmite automáticamente al aire
                    mediaRecorder.onstop = () => {
                        if (micStatus) {
                            micStatus.textContent = '🚀 TRANSMITIENDO AL AIRE CON ATENUACIÓN DE MÚSICA...';
                            micStatus.className = 'badge bg-warning text-dark animate-pulse px-3 py-2 fs-6 mb-3';
                        }
                        if (recBtn) {
                            recBtn.disabled = true;
                            recLabel.textContent = 'ENVIANDO...';
                        }

                        const blob = new Blob(audioChunks, { type: 'audio/webm' });
                        const formData = new FormData();
                        formData.append('mic_audio', blob, 'mic_recording.webm');
                        formData.append('csrf_token', CSRF_TOKEN);

                        fetch(BROADCAST_MIC_URL, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': CSRF_TOKEN
                            }
                        })
                        .then(r => r.json())
                        .then(j => {
                            if (j.ok) {
                                if (micStatus) {
                                    micStatus.textContent = '¡VOZ TRANSMITIDA EXITOSAMENTE AL AIRE!';
                                    micStatus.className = 'badge bg-success px-3 py-2 fs-6 mb-3';
                                }
                                setTimeout(() => {
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('micStudioModal'));
                                    if (modal) modal.hide();
                                    location.reload();
                                }, 1500);
                            } else {
                                alert('Error al transmitir voz: ' + (j.message || 'Error desconocido'));
                                location.reload();
                            }
                        })
                        .catch(e => {
                            alert('Error de conexión al enviar voz al aire: ' + e.message);
                            location.reload();
                        });
                    };

                    // VU Meter
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    analyser = audioContext.createAnalyser();
                    microphone = audioContext.createMediaStreamSource(stream);
                    javascriptNode = audioContext.createScriptProcessor(2048, 1, 1);

                    analyser.smoothingTimeConstant = 0.8;
                    analyser.fftSize = 1024;

                    microphone.connect(analyser);
                    analyser.connect(javascriptNode);
                    javascriptNode.connect(audioContext.destination);

                    javascriptNode.onaudioprocess = () => {
                        const array = new Uint8Array(analyser.frequencyBinCount);
                        analyser.getByteFrequencyData(array);
                        let values = 0;
                        for (let i = 0; i < array.length; i++) values += array[i];
                        const average = values / array.length;
                        if (vumeter) vumeter.style.width = Math.min(100, Math.round(average * 2.2)) + '%';
                    };

                    mediaRecorder.start();
                    startTime = Date.now();
                    timerInterval = setInterval(() => {
                        const diff = Math.floor((Date.now() - startTime) / 1000);
                        const m = String(Math.floor(diff / 60)).padStart(2, '0');
                        const s = String(diff % 60).padStart(2, '0');
                        if (timerEl) timerEl.textContent = `${m}:${s}`;
                    }, 1000);

                    recBtn.classList.replace('btn-danger', 'btn-warning');
                    recBtn.classList.add('animate-pulse');
                    recLabel.textContent = 'DETENER';
                    if (micStatus) {
                        micStatus.textContent = '🔴 GRABANDO VOZ EN VIVO...';
                        micStatus.className = 'badge bg-danger px-3 py-2 fs-6 mb-3';
                    }
                    if (timerEl) timerEl.classList.remove('d-none');
                } catch (err) {
                    alert('No se pudo acceder al micrófono: ' + err.message);
                }
            } else if (mediaRecorder.state === 'recording') {
                // Al volver a hacer clic, se detiene la grabación y dispara onstop
                mediaRecorder.stop();
                clearInterval(timerInterval);
                if (javascriptNode) javascriptNode.disconnect();
                if (analyser) analyser.disconnect();
            }
        });
    }
});
</script>
