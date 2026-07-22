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
$streamUrl = "http://{$host}:{$port}/stream";
$sid = (int) $station['id'];
$running = ($station['status'] ?? '') === 'running';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-broadcast-pin text-info"></i> <?= e($station['name']) ?> <?= status_badge($station['status']) ?></h5>
        <?php if (!empty($owner)): ?><div class="small text-muted">Cliente: <?= e($owner['name']) ?></div><?php endif; ?>
    </div>
    <div class="btn-group">
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

    <!-- Datos de conexion -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">Datos de conexion</div>
            <div class="card-body small">
                <div class="mb-2"><span class="text-muted">URL del stream:</span><br><code class="copyable"><?= e($streamUrl) ?></code></div>
                <div class="mb-2"><span class="text-muted">Servidor (para tu software DJ):</span><br>
                    Host <code><?= e($host) ?></code> · Puerto <code><?= $port ?></code> · Mount <code>/stream</code></div>
                <div class="mb-2"><span class="text-muted">Contrasena de fuente (source):</span><br>
                    <code class="copyable"><?= e($station['source_password']) ?></code></div>
                <?php if ($showSensitive && !empty($adminPass)): ?>
                    <div class="mb-2"><span class="text-muted">Panel admin Shoutcast:</span><br>
                        <code><?= e("http://{$host}:{$port}/admin.cgi") ?></code> · user <code>admin</code> · pass <code class="copyable"><?= e($adminPass) ?></code></div>
                <?php endif; ?>
                <div><span class="text-muted">Bitrate max:</span> <?= (int) $station['max_bitrate'] ?> kbps ·
                     <span class="text-muted">Oyentes max:</span> <?= (int) $station['max_listeners'] ?></div>
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
