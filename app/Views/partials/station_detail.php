<?php
/**
 * Detalle de estacion reutilizable.
 * Vars: $station (con hostname/driver via JOIN), $base ('admin'|'client'|'reseller'),
 *       $latest (ultimo snapshot|null), $adminPass (string|null), $showSensitive (bool),
 *       $owner (array|null)
 */
$base = $base ?? 'admin';
$showSensitive = $showSensitive ?? true;
$host = $station['hostname'] ?? '127.0.0.1';
$port = (int) $station['port'];
$reqHost = $_SERVER['HTTP_HOST'] ?? $host;
$domain = parse_url('https://' . $reqHost, PHP_URL_HOST) ?: $host;
$webPort = parse_url('https://' . $reqHost, PHP_URL_PORT) ?: 7000;

$streamUrlSsl  = "https://{$domain}:{$webPort}/listen/{$port}/stream";
$streamUrlHttp = "http://{$domain}:{$port}/stream";
$adminUrlSsl   = "https://{$domain}:{$webPort}/listen/{$port}/admin.cgi";
$sid = (int) $station['id'];
$running = ($station['status'] ?? '') === 'running';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-broadcast-pin text-info"></i> <?= e($station['name']) ?> <?= status_badge($station['status']) ?></h5>
        <?php if (!empty($owner)): ?><div class="small text-muted">Cliente: <?= e($owner['name']) ?></div><?php endif; ?>
    </div>
    <div class="btn-group">
        <a href="<?= url($base . '/stations/' . $sid . '/analytics') ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-globe-americas"></i> Analíticas y Mapa</a>
        <?php if ($base === 'admin'): ?>
            <a href="<?= url('admin/stations/' . $sid . '/edit') ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>
        <?php endif; ?>
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

<!-- Reproductor de Audio Preescuchar al Inicio -->
<div class="card mb-4 border-0 shadow-lg bg-dark text-white overflow-hidden position-relative" style="background: linear-gradient(135deg, #181c2b 0%, #0d111a 100%); border-left: 4px solid #0dcaf0 !important;">
    <div class="card-body p-3 p-md-4">
        <div class="row align-items-center g-3">
            <div class="col-auto">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 d-flex align-items-center justify-content-center text-info shadow-sm" style="width:60px; height:60px; border: 2px solid rgba(13, 202, 240, 0.3);">
                    <i class="bi bi-disc-fill fs-2"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-success bg-gradient text-white px-2 py-1"><i class="bi bi-broadcast"></i> EMISIÓN EN VIVO</span>
                    <span class="badge bg-dark border text-light"><i class="bi bi-lock-fill text-success"></i> HTTPS SSL</span>
                </div>
                <h5 class="mb-1 text-white fw-bold"><i class="bi bi-music-note-beamer text-info"></i> Preescuchar Radio: <?= e($station['name']) ?></h5>
                <div class="small text-white-50 text-truncate" style="max-width:450px;">
                    <i class="bi bi-music-note"></i> Ahora suena: <strong id="topNowPlaying" class="text-info"><?= e($latest['song_title'] ?? 'Cargando tema en vivo...') ?></strong>
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

<!-- Enlaces de Reproducción y Streaming URL -->
<div class="card mb-4 shadow-sm border-primary">
    <div class="card-header bg-primary-subtle text-primary-emphasis fw-bold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-link-45deg text-primary fs-5 me-1"></i> Direcciones URL de Reproducción y Streaming (HTTPS & HTTP)</span>
        <span class="badge bg-primary">Enlaces Públicos</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-success mb-1">
                    <i class="bi bi-lock-fill"></i> Stream HTTPS Seguro (Para reproductores web, Apps e iOS)
                </label>
                <div class="input-group input-group-sm mb-1">
                    <input type="text" class="form-control font-monospace text-success fw-bold" readonly value="<?= e($streamUrlSsl) ?>" id="streamUrlSslInput">
                    <button class="btn btn-outline-success" onclick="copyInput('streamUrlSslInput', this)" title="Copiar URL HTTPS">
                        <i class="bi bi-clipboard"></i> Copiar
                    </button>
                </div>
                <div class="form-text small">Usa este enlace HTTPS para reproductores en sitios web SSL o aplicaciones móviles.</div>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-info mb-1">
                    <i class="bi bi-globe"></i> Stream HTTP Estándar (Puerto directo)
                </label>
                <div class="input-group input-group-sm mb-1">
                    <input type="text" class="form-control font-monospace text-info fw-bold" readonly value="<?= e($streamUrlHttp) ?>" id="streamUrlHttpInput">
                    <button class="btn btn-outline-info" onclick="copyInput('streamUrlHttpInput', this)" title="Copiar URL HTTP">
                        <i class="bi bi-clipboard"></i> Copiar
                    </button>
                </div>
                <div class="form-text small">Usa este enlace para reproductores de escritorio tradicionales (Winamp, VLC, TuneIn).</div>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-warning mb-1">
                    <i class="bi bi-gear-wide-connected"></i> Panel de Administración Shoutcast DNAS v2 (HTTPS)
                </label>
                <div class="input-group input-group-sm mb-1">
                    <input type="text" class="form-control font-monospace text-warning fw-bold" readonly value="<?= e($adminUrlSsl) ?>" id="adminUrlSslInput">
                    <a href="<?= e($adminUrlSsl) ?>" target="_blank" class="btn btn-outline-warning" title="Abrir Admin DNAS">
                        <i class="bi bi-box-arrow-up-right"></i> Abrir
                    </a>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-bold text-light mb-1">
                    <i class="bi bi-file-earmark-music"></i> Listas de Reproducción (.M3U / .PLS)
                </label>
                <div class="d-flex gap-2">
                    <a href="<?= e($streamUrlSsl) ?>.m3u" class="btn btn-sm btn-outline-light flex-fill" target="_blank">
                        <i class="bi bi-download"></i> Descargar Playlist .M3U
                    </a>
                    <a href="<?= e($streamUrlSsl) ?>.pls" class="btn btn-sm btn-outline-light flex-fill" target="_blank">
                        <i class="bi bi-download"></i> Descargar Playlist .PLS
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Estado en vivo -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">Estado en vivo</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Oyentes ahora</span>
                    <span class="fs-4 fw-bold text-info" id="liveListeners"><?= (int) ($latest['current_listeners'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Pico</span>
                    <span id="peakListeners"><?= (int) ($latest['peak_listeners'] ?? 0) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Estado</span>
                    <span id="upState"><?= !empty($latest['is_up']) ? '<span class="badge bg-success">Al aire</span>' : '<span class="badge bg-secondary">Fuera</span>' ?></span>
                </div>
                <hr>
                <div class="text-muted small">Ahora suena</div>
                <div id="nowPlaying" class="text-truncate"><?= e($latest['song_title'] ?? '—') ?></div>
            </div>
        </div>
    </div>

    <!-- Grafica -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">Oyentes (historico reciente)</div>
            <div class="card-body">
                <canvas id="listenersChart" height="110"></canvas>
            </div>
        </div>
    </div>

    <!-- Datos de conexion en Vivo -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm border-info">
            <div class="card-header bg-info-subtle fw-bold text-info-emphasis d-flex justify-content-between align-items-center">
                <span><i class="bi bi-broadcast text-info"></i> Conexión en Vivo (VirtualDJ, ZaraRadio, OBS, RadioBOSS)</span>
                <span class="badge bg-info">Encoder / DJ</span>
            </div>
            <div class="card-body small">
                <div class="alert alert-dark p-2 mb-3 border">
                    <i class="bi bi-info-circle text-info"></i> Usa estos datos en tu programa de transmisión (VirtualDJ, ZaraRadio, BUTT, RadioBOSS, OBS, Mixxx) para emitir en vivo a la radio.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-2">
                        <tbody>
                            <tr>
                                <th class="bg-body-tertiary" style="width:40%;">Servidor / Host</th>
                                <td>
                                    <code class="copyable text-info fw-bold"><?= e($domain) ?></code>
                                    <span class="text-muted ms-1">(IP: <code><?= e($host) ?></code>)</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-body-tertiary">Puerto DJ (AutoDJ Harbor)</th>
                                <td>
                                    <code class="copyable text-success fw-bold"><?= (int) ($station['dj_port'] ?? ($port + 10000)) ?></code>
                                    <span class="badge bg-success ms-1">Recomendado DJ en Vivo</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-body-tertiary">Puerto Directo (Shoutcast)</th>
                                <td>
                                    <code class="copyable"><?= $port ?></code>
                                    <span class="text-muted ms-1">(Sin AutoDJ)</span>
                                </td>
                            </tr>
                            <tr>
                                <th class="bg-body-tertiary">Punto de Montaje (Mount)</th>
                                <td><code class="copyable">/stream</code> <span class="text-muted">(Shoutcast / Icecast)</span></td>
                            </tr>
                            <tr>
                                <th class="bg-body-tertiary">Usuario (Source User)</th>
                                <td><code class="copyable">source</code> <span class="text-muted">(o vacio según programa)</span></td>
                            </tr>
                            <tr>
                                <th class="bg-body-tertiary">Contraseña (Source Password)</th>
                                <td><code class="copyable fw-bold text-danger"><?= e($station['source_password']) ?></code></td>
                            </tr>
                            <tr>
                                <th class="bg-body-tertiary">Formato / Bitrate Máx</th>
                                <td><code>MP3</code> / <code>AAC+</code> a <strong><?= (int) $station['max_bitrate'] ?> kbps</strong> (Oyentes máx: <?= (int) $station['max_listeners'] ?>)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-body-tertiary p-2 rounded border mt-2">
                    <strong class="d-block mb-1 text-muted"><i class="bi bi-sliders"></i> Ejemplos por programa:</strong>
                    <ul class="mb-0 ps-3 text-muted">
                        <li><strong>VirtualDJ / RadioBOSS:</strong> Servidor: <code><?= e($domain) ?>:<?= (int) ($station['dj_port'] ?? ($port + 10000)) ?></code> | Pass: <code><?= e($station['source_password']) ?></code> | Tipo: <code>Shoutcast</code></li>
                        <li><strong>ZaraRadio + Oddcast / BUTT:</strong> Host: <code><?= e($domain) ?></code> | Port: <code><?= (int) ($station['dj_port'] ?? ($port + 10000)) ?></code> | Pass: <code><?= e($station['source_password']) ?></code> | Mount: <code>/stream</code></li>
                        <li><strong>OBS Studio:</strong> Tipo: Custom HTTP/Icecast | URL: <code>icecast://<?= e($domain) ?>:<?= (int) ($station['dj_port'] ?? ($port + 10000)) ?>/stream</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- AutoDJ -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>AutoDJ</span>
                <?= ($station['autodj_enabled'] ?? 0)
                    ? '<span class="badge bg-info">Habilitado</span>'
                    : '<span class="badge bg-secondary">Deshabilitado</span>' ?>
            </div>
            <div class="card-body">
                <?php if ($station['autodj_enabled'] ?? 0): ?>
                    <p class="small text-muted mb-2">
                        Estado del AutoDJ:
                        <?= ($station['autodj_status'] ?? 'stopped') === 'running'
                            ? '<span class="badge bg-success">Reproduciendo</span>'
                            : '<span class="badge bg-secondary">Detenido</span>' ?>
                    </p>
                    <p class="small text-muted">DJ en vivo (harbor Liquidsoap): host <code><?= e($host) ?></code> · puerto <code><?= (int) ($station['dj_port'] ?? 0) ?></code></p>
                    <a href="<?= url($base . '/stations/' . $sid . '/autodj') ?>" class="btn btn-sm btn-info">
                        <i class="bi bi-music-note-list"></i> Administrar musica y playlists
                    </a>
                <?php else: ?>
                    <p class="small text-muted mb-0">El AutoDJ esta deshabilitado para esta estacion.
                        <?php if ($base === 'admin'): ?>Actívalo desde <a href="<?= url('admin/stations/' . $sid . '/edit') ?>">editar estacion</a>.<?php endif; ?>
                    </p>
                <?php endif; ?>
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
        chart = new Chart(ctx, {
            type: 'line',
            data: { labels: labels, datasets: [{
                label: 'Oyentes', data: data, tension: 0.3,
                borderColor: '#6c5ce7', backgroundColor: 'rgba(108,92,231,.15)', fill: true, pointRadius: 0
            }]},
            options: { responsive: true, plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
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
                document.getElementById('liveListeners').textContent = j.current_listeners ?? 0;
                document.getElementById('peakListeners').textContent = j.peak_listeners ?? 0;
                document.getElementById('nowPlaying').textContent = j.song_title || '—';
                if (document.getElementById('topNowPlaying')) document.getElementById('topNowPlaying').textContent = j.song_title || '—';
                document.getElementById('upState').innerHTML = j.is_up
                    ? '<span class="badge bg-success">Al aire</span>'
                    : '<span class="badge bg-secondary">Fuera</span>';
                if (chart) {
                    const t = new Date().toLocaleTimeString().slice(0,5);
                    chart.data.labels.push(t);
                    chart.data.datasets[0].data.push(j.current_listeners ?? 0);
                    if (chart.data.labels.length > 60) { chart.data.labels.shift(); chart.data.datasets[0].data.shift(); }
                    chart.update('none');
                }
            }).catch(() => {});
    }, 10000);

    // Copiar al portapapeles
    document.querySelectorAll('code.copyable').forEach(el => {
        el.addEventListener('click', () => navigator.clipboard?.writeText(el.textContent));
        el.title = 'Clic para copiar';
    });
});
</script>
