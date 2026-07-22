<?php
/** @var array $station; @var string $base; @var int $days; @var array $kpis; @var array $countries; @var array $cities; @var array $devices; @var array $players */
$sid = (int) $station['id'];
$analyticsUrl = url($base . '/stations/' . $sid . '/analytics');
$apiUrl = url($base . '/stations/' . $sid . '/analytics/api');
?>
<!-- Leaflet.js para Mapa Mundial -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <a href="<?= url($base . '/stations/' . $sid) ?>" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Volver a la estación</a>
        <h5 class="mb-0 mt-1"><i class="bi bi-globe-americas text-info"></i> Analíticas de Audiencia y Mapa Mundial · <?= e($station['name']) ?></h5>
    </div>
    <div class="d-flex align-items-center gap-2">
        <label class="small text-muted fw-bold mb-0">Periodo:</label>
        <select class="form-select form-select-sm" style="width:140px;" onchange="location.href='<?= $analyticsUrl ?>?days='+this.value">
            <option value="1" <?= $days === 1 ? 'selected' : '' ?>>Últimas 24 horas</option>
            <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Últimos 7 días</option>
            <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Últimos 30 días</option>
            <option value="90" <?= $days === 90 ? 'selected' : '' ?>>Últimos 90 días</option>
        </select>
    </div>
</div>

<!-- Tarjetas KPI -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm bg-body-tertiary">
            <div class="card-body p-3">
                <div class="text-muted small fw-bold">OYENTES ÚNICOS</div>
                <div class="fs-3 fw-bold text-info"><?= number_format($kpis['unique_listeners'] ?? 0) ?></div>
                <div class="small text-muted"><i class="bi bi-people"></i> IPs distintas conectadas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm bg-body-tertiary">
            <div class="card-body p-3">
                <div class="text-muted small fw-bold">PICO MÁXIMO OYENTES</div>
                <div class="fs-3 fw-bold text-success"><?= number_format($kpis['peak_listeners'] ?? 0) ?></div>
                <div class="small text-muted"><i class="bi bi-graph-up-arrow"></i> Simultáneos en vivo</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm bg-body-tertiary">
            <div class="card-body p-3">
                <div class="text-muted small fw-bold">PROMEDIO DE ESCUCHA</div>
                <div class="fs-3 fw-bold text-warning"><?= round(($kpis['avg_duration_sec'] ?? 0) / 60, 1) ?> min</div>
                <div class="small text-muted"><i class="bi bi-clock-history"></i> Tiempo por sesión</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm bg-body-tertiary">
            <div class="card-body p-3">
                <div class="text-muted small fw-bold">TRAFICO DE RED</div>
                <div class="fs-3 fw-bold text-primary"><?= human_size((int) ($kpis['total_bytes'] ?? 0)) ?></div>
                <div class="small text-muted"><i class="bi bi-hdd-network"></i> Ancho de banda transferido</div>
            </div>
        </div>
    </div>
</div>

<!-- MAPA MUNDIAL DE OYENTES INTERACTIVO -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-geo-alt-fill text-danger"></i> Mapa Mundial de Ubicación de Oyentes</span>
        <span class="badge bg-primary" id="mapMarkerCount">Cargando ubicación de audiencia...</span>
    </div>
    <div class="card-body p-0">
        <div id="analyticsMap" style="height: 440px; width: 100%; border-bottom-left-radius: .375rem; border-bottom-right-radius: .375rem;"></div>
    </div>
</div>

<div class="row g-3">
    <!-- Tendencia de Oyentes & Paises -->
    <div class="col-lg-7">
        <!-- Grafica de historia -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header fw-bold"><i class="bi bi-graph-up text-primary"></i> Tendencia Reciente de Audiencia</div>
            <div class="card-body">
                <canvas id="analyticsHistoryChart" height="130"></canvas>
            </div>
        </div>

        <!-- Top Paises -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-flag-fill text-warning"></i> Principales Países Oyentes</span>
                <span class="small text-muted">Top 10</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>País</th>
                                <th>Código</th>
                                <th class="text-end">Sesiones</th>
                                <th class="text-end">IPs Únicas</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$countries): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Aún no hay datos suficientes registrados.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($countries as $c): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <span class="fi fi-<?= strtolower(e($c['country_code'])) ?> me-1"></span> <?= e($c['country']) ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= e($c['country_code']) ?></span></td>
                                <td class="text-end fw-bold"><?= number_format((int) $c['sessions_count']) ?></td>
                                <td class="text-end text-muted"><?= number_format((int) $c['unique_count']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Dispositivos & Reproductores -->
    <div class="col-lg-5">
        <!-- Dispositivos -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header fw-bold"><i class="bi bi-phone-laptop text-info"></i> Tipos de Dispositivo</div>
            <div class="card-body">
                <canvas id="deviceChart" height="160"></canvas>
            </div>
        </div>

        <!-- Reproductores / Navegadores -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header fw-bold"><i class="bi bi-app-indicator text-success"></i> Reproductores y Navegadores</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                <?php if (!$players): ?>
                    <li class="list-group-item text-center text-muted py-3">Aún no hay datos de reproductores.</li>
                <?php endif; ?>
                <?php foreach ($players as $p): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-music-player text-info me-2"></i> <?= e($p['player_name']) ?></span>
                        <span class="badge bg-primary rounded-pill"><?= number_format((int) $p['count']) ?></span>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Ciudades -->
        <div class="card mb-3 shadow-sm">
            <div class="card-header fw-bold"><i class="bi bi-building text-danger"></i> Top Ciudades</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush small">
                <?php if (!$cities): ?>
                    <li class="list-group-item text-center text-muted py-3">Sin ciudades registradas.</li>
                <?php endif; ?>
                <?php foreach ($cities as $ci): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-geo-alt text-danger me-1"></i> <?= e($ci['city']) ?>, <span class="text-muted"><?= e($ci['country']) ?></span></span>
                        <span class="badge bg-dark border"><?= number_format((int) $ci['sessions_count']) ?> sesiones</span>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.beacon-live {
    position: relative;
    width: 26px;
    height: 26px;
}
.beacon-live .dot {
    position: absolute;
    top: 50%; left: 50%;
    width: 14px; height: 14px;
    margin: -7px 0 0 -7px;
    background: #2ecc71;
    border: 2px solid #ffffff;
    border-radius: 50%;
    box-shadow: 0 0 12px #2ecc71;
}
.beacon-live::after {
    content: '';
    position: absolute;
    width: 34px; height: 34px;
    top: 50%; left: 50%;
    margin: -17px 0 0 -17px;
    border: 2px solid #2ecc71;
    border-radius: 50%;
    animation: beacon-ping 1.6s infinite ease-out;
}
@keyframes beacon-ping {
    0% { transform: scale(0.3); opacity: 1; }
    100% { transform: scale(2.2); opacity: 0; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const apiUrl = '<?= $apiUrl ?>?days=<?= $days ?>';

    // Inicializar Mapa Leaflet con modo oscuro CartoDB Dark
    const map = L.map('analyticsMap').setView([-25.2637, -57.5759], 3);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 18
    }).addTo(map);

    let historyChart;
    let deviceChart;
    let markerLayerGroup = L.featureGroup().addTo(map);

    const liveIcon = L.divIcon({
        className: 'beacon-live-container',
        html: '<div class="beacon-live"><span class="dot"></span></div>',
        iconSize: [26, 26],
        iconAnchor: [13, 13]
    });

    function loadAnalyticsData() {
        fetch(apiUrl)
            .then(r => r.json())
            .then(data => {
                if (!data.ok) return;

                markerLayerGroup.clearLayers();
                const markers = data.markers || [];
                document.getElementById('mapMarkerCount').textContent = markers.length + ' ubicaciones encontradas';

                let firstLiveMarker = null;

                markers.forEach(m => {
                    const lat = parseFloat(m.latitude);
                    const lng = parseFloat(m.longitude);
                    if (isNaN(lat) || isNaN(lng)) return;

                    const isLive = parseInt(m.is_live) === 1;
                    let marker;

                    if (isLive) {
                        marker = L.marker([lat, lng], { icon: liveIcon });
                        if (!firstLiveMarker) firstLiveMarker = marker;
                    } else {
                        marker = L.circleMarker([lat, lng], {
                            radius: 6,
                            fillColor: '#3498db',
                            color: '#ffffff',
                            weight: 1.5,
                            opacity: 1,
                            fillOpacity: 0.8
                        });
                    }

                    const popupHtml = `
                        <div style="min-width:190px; font-family:sans-serif; padding:2px;">
                            <strong style="color:${isLive ? '#2ecc71' : '#3498db'}; fs-6">
                                ${isLive ? '🔴 OYENTE EN VIVO AL AIRE' : '⏱️ Sesión Histórica'}
                            </strong><br>
                            <div style="margin-top:4px;">
                                <strong>📍 ${m.city || 'Desconocida'}, ${m.country || ''}</strong><br>
                                <span style="color:#6c757d; font-size:12px;">IP: ${m.listener_ip}</span><br>
                                <span style="color:#6c757d; font-size:12px;">App: ${m.player_name || 'Desconocida'}</span><br>
                                <span style="color:#6c757d; font-size:12px;">Tiempo: ${Math.round((m.duration_seconds || 0)/60)} min</span>
                            </div>
                        </div>
                    `;
                    marker.bindPopup(popupHtml);
                    marker.addTo(markerLayerGroup);
                });

                if (markers.length > 0 && markerLayerGroup.getLayers().length > 0) {
                    map.fitBounds(markerLayerGroup.getBounds(), { padding: [40, 40], maxZoom: 7 });
                    if (firstLiveMarker) {
                        firstLiveMarker.openPopup();
                    }
                }

                // 2. Grafico Tendencia de Oyentes
                const ctxHist = document.getElementById('analyticsHistoryChart');
                if (ctxHist && data.history && !historyChart) {
                    historyChart = new Chart(ctxHist, {
                        type: 'line',
                        data: {
                            labels: data.history.labels || [],
                            datasets: [{
                                label: 'Oyentes Simultáneos',
                                data: data.history.data || [],
                                borderColor: '#0dcaf0',
                                backgroundColor: 'rgba(13,202,240,0.15)',
                                fill: true,
                                tension: 0.3,
                                pointRadius: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                        }
                    });
                } else if (historyChart && data.history) {
                    historyChart.data.labels = data.history.labels || [];
                    historyChart.data.datasets[0].data = data.history.data || [];
                    historyChart.update('none');
                }

                // 3. Grafico Donut Dispositivos
                const ctxDev = document.getElementById('deviceChart');
                if (ctxDev && data.devices && !deviceChart) {
                    const devLabels = (data.devices || []).map(d => d.device_type.toUpperCase());
                    const devData   = (data.devices || []).map(d => d.count);
                    deviceChart = new Chart(ctxDev, {
                        type: 'doughnut',
                        data: {
                            labels: devLabels.length ? devLabels : ['SIN DATOS'],
                            datasets: [{
                                data: devData.length ? devData : [1],
                                backgroundColor: ['#0dcaf0', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6']
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: { legend: { position: 'bottom' } }
                        }
                    });
                }
            })
            .catch(err => console.error('Error cargando analíticas API:', err));
    }

    loadAnalyticsData();
    setInterval(loadAnalyticsData, 10000);
});
</script>
