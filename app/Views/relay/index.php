<?php
/** @var array $station */
/** @var string $baseRole */
$mode = $station['relay_mode'] ?? 'fulltime';
$url  = $station['relay_url'] ?? '';
$sHour = substr((string)($station['relay_start_hour'] ?? '08:00'), 0, 5);
$eHour = substr((string)($station['relay_end_hour'] ?? '18:00'), 0, 5);
?>

<div class="container-fluid py-3">
    <!-- BREADCRUMB & HEADER -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small text-white-50">
                    <ol class="breadcrumb-item"><a href="<?= url($baseRole . '/stations/' . $station['id']) ?>" class="text-info text-decoration-none"><i class="bi bi-broadcast me-1"></i> <?= e($station['name']) ?></a></ol>
                    <ol class="breadcrumb-item active text-white" aria-current="page">Re-transmisión Relay & Horarios</ol>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-white mb-0">
                <i class="bi bi-arrow-repeat text-success me-2"></i> Re-transmisión Relay & Programación Horaria
            </h1>
            <p class="text-white-50 small mb-0">Configura la transmisión de radios remotas, cadenas nacionales, emisoras aliadas o respaldos 24/7 por horario.</p>
        </div>

        <div>
            <?php if ($mode === 'exclusive'): ?>
                <span class="badge bg-danger fs-6 px-3 py-2 shadow-sm"><i class="bi bi-broadcast me-1"></i> Relay Exclusivo Continuo 24/7</span>
            <?php elseif ($mode === 'scheduled'): ?>
                <span class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm"><i class="bi bi-clock me-1"></i> Relay Programado (<?= e($sHour) ?> a <?= e($eHour) ?>)</span>
            <?php elseif ($mode === 'disabled'): ?>
                <span class="badge bg-secondary fs-6 px-3 py-2 shadow-sm"><i class="bi bi-slash-circle me-1"></i> Relay Desactivado</span>
            <?php else: ?>
                <span class="badge bg-success fs-6 px-3 py-2 shadow-sm"><i class="bi bi-shield-check me-1"></i> Relay Full Time (Respaldo 24/7)</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    <?php if ($fl = flash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= e($fl['message'] ?? '') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- COLUMNA IZQUIERDA: FORMULARIO DE CONFIGURACIÓN & REPRODUCTOR -->
        <div class="col-lg-7">
            <div class="card bg-dark border-secondary shadow-lg mb-4" style="border-radius: 16px;">
                <div class="card-header bg-dark border-secondary py-3">
                    <h5 class="card-title fw-bold text-white mb-0">
                        <i class="bi bi-sliders text-info me-2"></i> Ajustes de Origen y Modo de Operación
                    </h5>
                </div>
                <div class="card-body py-4">
                    <form method="post" action="<?= url($baseRole . '/stations/' . $station['id'] . '/relay') ?>">
                        <?= \App\Core\Csrf::field() ?>

                        <!-- URL DEL STREAM -->
                        <div class="mb-4">
                            <label class="form-label text-light fw-bold fs-6">
                                <i class="bi bi-link-45deg me-1 text-success"></i> URL de Stream Relay Externo (Fuente de Origen)
                            </label>
                            <input type="url" name="relay_url" class="form-control form-control-lg bg-dark text-white border-secondary font-monospace" placeholder="https://stream.dominio.com/radio.mp3" value="<?= e($url) ?>" required>
                            <div class="form-text text-muted mt-2">
                                Soporta URLs directas de streams **MP3, AAC, HE-AAC, Icecast, Shoutcast, enlaces de Zeno.fm** y listas **M3U / PLS**.
                            </div>
                        </div>

                        <!-- MODO DE OPERACIÓN (TARJETAS RADIO) -->
                        <div class="mb-4">
                            <label class="form-label text-light fw-bold fs-6 mb-3">
                                <i class="bi bi-gear-wide-connected me-1 text-info"></i> Selecciona el Modo de Re-transmisión
                            </label>

                            <div class="row g-3">
                                <!-- FULLTIME -->
                                <div class="col-md-6">
                                    <div class="card bg-dark border-secondary h-100 p-3 relay-mode-card">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="relay_mode" id="modeFulltime" value="fulltime" <?= $mode === 'fulltime' ? 'checked' : '' ?> onchange="toggleScheduleInputs()">
                                            <label class="form-check-label text-white fw-bold cursor-pointer" for="modeFulltime">
                                                🔄 Full-Time (Respaldo 24/7)
                                            </label>
                                        </div>
                                        <p class="small text-white-50 mb-0 mt-2">
                                            Re-transmite la señal externa cuando no haya DJ en vivo transmitiendo ni AutoDJ local sonando. Ideal como música de respaldo.
                                        </p>
                                    </div>
                                </div>

                                <!-- SCHEDULED -->
                                <div class="col-md-6">
                                    <div class="card bg-dark border-secondary h-100 p-3 relay-mode-card">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="relay_mode" id="modeScheduled" value="scheduled" <?= $mode === 'scheduled' ? 'checked' : '' ?> onchange="toggleScheduleInputs()">
                                            <label class="form-check-label text-white fw-bold cursor-pointer" for="modeScheduled">
                                                ⏰ Por Horario Programado
                                            </label>
                                        </div>
                                        <p class="small text-white-50 mb-0 mt-2">
                                            Engancha la radio externa **únicamente entre las horas configuradas** (ej: noticieros o programas nocturnos).
                                        </p>
                                    </div>
                                </div>

                                <!-- EXCLUSIVE -->
                                <div class="col-md-6">
                                    <div class="card bg-dark border-secondary h-100 p-3 relay-mode-card">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="relay_mode" id="modeExclusive" value="exclusive" <?= $mode === 'exclusive' ? 'checked' : '' ?> onchange="toggleScheduleInputs()">
                                            <label class="form-check-label text-white fw-bold cursor-pointer" for="modeExclusive">
                                                📻 Relay Exclusivo Continuo
                                            </label>
                                        </div>
                                        <p class="small text-white-50 mb-0 mt-2">
                                            Retransmite la radio externa las 24 horas del día ignorando el AutoDJ local.
                                        </p>
                                    </div>
                                </div>

                                <!-- DISABLED -->
                                <div class="col-md-6">
                                    <div class="card bg-dark border-secondary h-100 p-3 relay-mode-card">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="relay_mode" id="modeDisabled" value="disabled" <?= $mode === 'disabled' ? 'checked' : '' ?> onchange="toggleScheduleInputs()">
                                            <label class="form-check-label text-white fw-bold cursor-pointer" for="modeDisabled">
                                                🚫 Relay Desactivado
                                            </label>
                                        </div>
                                        <p class="small text-white-50 mb-0 mt-2">
                                            Desactiva completamente la retransmisión de fuentes externas.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FRANJA HORARIA PROGRAMADA (CONDICIONAL) -->
                        <div id="scheduleInputsContainer" class="card bg-dark border-warning p-3 mb-4 <?= $mode === 'scheduled' ? '' : 'd-none' ?>">
                            <h6 class="fw-bold text-warning mb-3">
                                <i class="bi bi-clock-history me-1"></i> Programación de Franja Horaria de Relay
                            </h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label text-white small fw-bold">Hora de Inicio (Activación)</label>
                                    <input type="time" name="relay_start_hour" class="form-control bg-dark text-white border-secondary font-monospace" value="<?= e($sHour) ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-white small fw-bold">Hora de Fin (Desconexión)</label>
                                    <input type="time" name="relay_end_hour" class="form-control bg-dark text-white border-secondary font-monospace" value="<?= e($eHour) ?>">
                                </div>
                            </div>
                            <div class="form-text text-white-50 mt-2">
                                <i class="bi bi-info-circle me-1"></i> El sistema conmutará automáticamente al Relay externo exactamente a la hora de inicio y retornará al AutoDJ local al finalizar la hora límite.
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success btn-lg px-4 fw-bold">
                                <i class="bi bi-check-circle-fill me-2"></i> Guardar y Aplicar Configuración Relay
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- REPRODUCTOR DE VERIFICACIÓN EN VIVO -->
            <div class="card bg-dark border-secondary shadow-lg" style="border-radius: 16px;">
                <div class="card-header bg-dark border-secondary py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title fw-bold text-white mb-0">
                        <i class="bi bi-headphones text-success me-2"></i> Probador y Verificación de Audio al Aire
                    </h5>
                    <span class="badge bg-secondary" id="playerStatus">Listo</span>
                </div>
                <div class="card-body py-4 text-center">
                    <p class="small text-white-50 mb-3">Escucha la señal de tu radio en tiempo real para verificar que el Relay esté emitiendo con excelente calidad:</p>

                    <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                        <button type="button" class="btn btn-success btn-lg rounded-circle p-3 shadow" style="width: 64px; height: 64px;" onclick="togglePlayStream()" id="playStreamBtn">
                            <i class="bi bi-play-fill fs-2" id="playIcon"></i>
                        </button>
                    </div>

                    <div class="progress bg-secondary bg-opacity-25 mb-3" style="height: 10px; border-radius: 6px;">
                        <div id="playerVuMeter" class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>

                    <div class="text-white-50 font-monospace small">
                        Stream URL: <a href="http://sonic.geeks.com.py:<?= (int)$station['port'] ?>/stream" target="_blank" class="text-info text-decoration-none">http://sonic.geeks.com.py:<?= (int)$station['port'] ?>/stream</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: TUTORIAL & GUÍA PASO A PASO DETALLADA -->
        <div class="col-lg-5">
            <div class="card bg-dark border-info shadow-lg" style="border-radius: 16px;">
                <div class="card-header bg-dark border-info py-3">
                    <h5 class="card-title fw-bold text-info mb-0">
                        <i class="bi bi-book-half me-2"></i> Tutorial & Guía de Re-transmisión Relay
                    </h5>
                </div>
                <div class="card-body py-4">
                    <!-- PASO 1 -->
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <span class="badge bg-info text-dark rounded-circle fs-5 px-3 py-2 fw-bold">1</span>
                        </div>
                        <div>
                            <h6 class="fw-bold text-white mb-1">¿Qué es el servicio de Relay?</h6>
                            <p class="small text-white-50 mb-0">
                                El Relay es un puente de re-transmisión que permite enganchar tu estación de radio a una fuente externa originaria en tiempo real (como radios aliadas, señales de satélite, canales de noticias o servidores Zeno.fm).
                            </p>
                        </div>
                    </div>

                    <!-- PASO 2 -->
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <span class="badge bg-info text-dark rounded-circle fs-5 px-3 py-2 fw-bold">2</span>
                        </div>
                        <div>
                            <h6 class="fw-bold text-white mb-1">Formatos de URL Compatibles</h6>
                            <p class="small text-white-50 mb-2">
                                Antigravity SonicStreaming integra un decodificador universal con soporte para:
                            </p>
                            <ul class="small text-white-50 ps-3 mb-0">
                                <li><strong>Streams HTTP / HTTPS directos:</strong> MP3, AAC, AAC+, HE-AAC (64k-320k).</li>
                                <li><strong>Servidores remotos:</strong> Icecast, Shoutcast DNAS v1 / v2.</li>
                                <li><strong>Listas de Reproducción:</strong> Enlaces terminados en <code>.m3u</code> o <code>.pls</code> (ej: Zeno.fm).</li>
                            </ul>
                        </div>
                    </div>

                    <!-- PASO 3 -->
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <span class="badge bg-info text-dark rounded-circle fs-5 px-3 py-2 fw-bold">3</span>
                        </div>
                        <div>
                            <h6 class="fw-bold text-white mb-1">Modos de Operación Explicados</h6>
                            <ul class="small text-white-50 ps-3 mb-0">
                                <li><strong>🔄 Full-Time (Respaldo 24/7):</strong> Tu AutoDJ local funciona normalmente. Si apagas el AutoDJ o no hay canciones, el Relay entra automáticamente sin dejar tu radio en silencio.</li>
                                <li><strong>⏰ Por Horario Programado:</strong> Define las horas exactas de inicio y fin. A la hora fijada, el panel enganchará la radio externa y retornará a tu playlist al finalizar.</li>
                                <li><strong>📻 Relay Exclusivo:</strong> Tu servidor retransmitirá las 24 horas del día la señal externa.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- PASO 4 -->
                    <div class="d-flex gap-3 mb-2">
                        <div class="flex-shrink-0">
                            <span class="badge bg-info text-dark rounded-circle fs-5 px-3 py-2 fw-bold">4</span>
                        </div>
                        <div>
                            <h6 class="fw-bold text-white mb-1">Consejos & Recomendaciones</h6>
                            <p class="small text-white-50 mb-0">
                                Asegúrate de ingresar una URL externa activa. Si la radio fuente se desconecta, la protección contra silencio mantendrá tu stream conectado sin interrupciones para tus oyentes.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleScheduleInputs() {
    const isScheduled = document.getElementById('modeScheduled').checked;
    const container = document.getElementById('scheduleInputsContainer');
    if (isScheduled) {
        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
    }
}

let audioPlayer = null;
let isPlaying = false;

function togglePlayStream() {
    const btn = document.getElementById('playStreamBtn');
    const icon = document.getElementById('playIcon');
    const status = document.getElementById('playerStatus');
    const vu = document.getElementById('playerVuMeter');
    const streamUrl = 'http://sonic.geeks.com.py:<?= (int)$station['port'] ?>/stream';

    if (!audioPlayer) {
        audioPlayer = new Audio(streamUrl + '?t=' + Date.now());
    }

    if (!isPlaying) {
        audioPlayer.src = streamUrl + '?t=' + Date.now();
        audioPlayer.play().then(() => {
            isPlaying = true;
            icon.className = 'bi bi-pause-fill fs-2';
            btn.classList.replace('btn-success', 'btn-warning');
            status.className = 'badge bg-success';
            status.textContent = 'En Vivo';
            vu.style.width = '85%';
        }).catch(err => {
            alert('Error al reproducir stream: ' + err.message);
        });
    } else {
        audioPlayer.pause();
        isPlaying = false;
        icon.className = 'bi bi-play-fill fs-2';
        btn.classList.replace('btn-warning', 'btn-success');
        status.className = 'badge bg-secondary';
        status.textContent = 'Listo';
        vu.style.width = '0%';
    }
}
</script>
