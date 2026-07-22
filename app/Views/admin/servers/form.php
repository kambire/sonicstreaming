<?php
/** @var array|null $server */
$isEdit = $server !== null;
$action = $isEdit ? url('admin/servers/' . $server['id']) : url('admin/servers');
$driver = $server['driver'] ?? old('driver', 'linux');
$status = $server['status'] ?? old('status', 'active');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold text-white"><i class="bi bi-server text-info me-2"></i><?= $isEdit ? 'Configuración del Servidor: ' . e($server['name']) : 'Agregar Nuevo Servidor' ?></h4>
        <div class="small text-muted">Ajusta los parámetros globales de red, puertos, credenciales SSH y límites por defecto.</div>
    </div>
    <a href="<?= url('admin/servers') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<form method="post" action="<?= $action ?>">
    <?= \App\Core\Csrf::field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <!-- SECCIÓN 1: IDENTIFICACIÓN Y RED PÚBLICA -->
    <div class="card mb-4 border-0 shadow-sm bg-dark bg-opacity-50" style="border-left: 4px solid #0dcaf0 !important;">
        <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 fw-bold text-white">
            <i class="bi bi-globe text-info me-2"></i> 1. Identificación y Parámetros de Red Pública (FQDN / IP)
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-light fw-bold">Nombre del Servidor</label>
                    <input type="text" name="name" class="form-control bg-dark text-white border-secondary" required placeholder="ej: Servidor Principal Node-1" value="<?= e($server['name'] ?? old('name', 'Servidor Principal Node 1')) ?>">
                    <div class="form-text text-muted">Nombre descriptivo para identificar este nodo en el panel.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-light fw-bold">Nombre de Host FQDN (Dominio público)</label>
                    <input type="text" name="hostname" class="form-control bg-dark text-white border-secondary" required placeholder="ej: sonic.geeks.com.py" value="<?= e($server['hostname'] ?? old('hostname', 'sonic.geeks.com.py')) ?>">
                    <div class="form-text text-muted">Nombre de dominio o FQDN con certificado SSL activo.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Dirección IP Pública</label>
                    <input type="text" name="public_ip" class="form-control bg-dark text-white border-secondary" placeholder="ej: 186.182.28.19" value="<?= e($server['public_ip'] ?? old('public_ip', '186.182.28.19')) ?>">
                    <div class="form-text text-muted">IP estática de salida para transmisiones de radio.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Puerto Panel HTTPS SSL</label>
                    <input type="number" name="ssl_port" class="form-control bg-dark text-white border-secondary" required value="<?= e((string) ($server['ssl_port'] ?? old('ssl_port', '7000'))) ?>">
                    <div class="form-text text-muted">Puerto HTTPS seguro del proxy web (ej: 7000).</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Ubicación / Datacenter</label>
                    <input type="text" name="datacenter_location" class="form-control bg-dark text-white border-secondary" placeholder="ej: Asunción, Paraguay" value="<?= e($server['datacenter_location'] ?? old('datacenter_location', 'Asunción, Paraguay (Geeks DataCenter)')) ?>">
                    <div class="form-text text-muted">Ciudad o proveedor de alojamiento.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: CONTROL DE PUERTOS Y DRIVER LIQUIDSOAP / SHOUTCAST -->
    <div class="card mb-4 border-0 shadow-sm bg-dark bg-opacity-50" style="border-left: 4px solid #10b981 !important;">
        <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 fw-bold text-white">
            <i class="bi bi-diagram-3 text-success me-2"></i> 2. Control de Emisión, Rangos de Puertos y Offsets
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Driver de Motor de Servidor</label>
                    <select name="driver" class="form-select bg-dark text-white border-secondary">
                        <option value="linux"   <?= $driver === 'linux' ? 'selected' : '' ?>>Linux (systemd / Liquidsoap + Shoutcast DNAS v2)</option>
                        <option value="windows" <?= $driver === 'windows' ? 'selected' : '' ?>>Windows (sc_serv.exe local / dev)</option>
                        <option value="mock"    <?= $driver === 'mock' ? 'selected' : '' ?>>Mock (Simulador en desarrollo)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Estado del Servidor</label>
                    <select name="status" class="form-select bg-dark text-white border-secondary">
                        <option value="active"   <?= $status === 'active' ? 'selected' : '' ?>>🟢 Activo (Asignando emisoras)</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>🔴 Inactivo (En mantenimiento)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Límite Máximo de Estaciones</label>
                    <input type="number" name="max_streams" class="form-control bg-dark text-white border-secondary" min="1" required value="<?= e((string) ($server['max_streams'] ?? old('max_streams', '200'))) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label text-light fw-bold">Puerto Inicial del Rango</label>
                    <input type="number" name="port_range_start" class="form-control bg-dark text-white border-secondary" min="1024" max="65535" required value="<?= e((string) ($server['port_range_start'] ?? old('port_range_start', '8010'))) ?>">
                    <div class="form-text text-muted">Ej: 8010.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label text-light fw-bold">Puerto Final del Rango</label>
                    <input type="number" name="port_range_end" class="form-control bg-dark text-white border-secondary" min="1024" max="65535" required value="<?= e((string) ($server['port_range_end'] ?? old('port_range_end', '8300'))) ?>">
                    <div class="form-text text-muted">Ej: 8300.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label text-light fw-bold">Offset Puerto DJ Harbor</label>
                    <input type="number" name="harbor_port_offset" class="form-control bg-dark text-white border-secondary" min="0" required value="<?= e((string) ($server['harbor_port_offset'] ?? old('harbor_port_offset', '10000'))) ?>">
                    <div class="form-text text-muted">Ej: 10000 (8010 -> 18010).</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label text-light fw-bold">Offset Puerto Telnet Socket</label>
                    <input type="number" name="telnet_port_offset" class="form-control bg-dark text-white border-secondary" min="0" required value="<?= e((string) ($server['telnet_port_offset'] ?? old('telnet_port_offset', '20000'))) ?>">
                    <div class="form-text text-muted">Ej: 20000 (8010 -> 28010).</div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 3: VALORES POR DEFECTO Y ACCESO SSH -->
    <div class="card mb-4 border-0 shadow-sm bg-dark bg-opacity-50" style="border-left: 4px solid #a855f7 !important;">
        <div class="card-header bg-transparent border-bottom border-secondary border-opacity-25 fw-bold text-white">
            <i class="bi bi-shield-lock text-purple me-2" style="color: #c084fc;"></i> 3. Parámetros por Defecto de Estaciones & Credenciales SSH
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Oyentes Máx por Defecto</label>
                    <input type="number" name="default_max_listeners" class="form-control bg-dark text-white border-secondary" required value="<?= e((string) ($server['default_max_listeners'] ?? old('default_max_listeners', '500'))) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Bitrate Máx por Defecto (kbps)</label>
                    <input type="number" name="default_max_bitrate" class="form-control bg-dark text-white border-secondary" required value="<?= e((string) ($server['default_max_bitrate'] ?? old('default_max_bitrate', '192'))) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label text-light fw-bold">Canciones Máx AutoDJ</label>
                    <input type="number" name="default_max_tracks" class="form-control bg-dark text-white border-secondary" required value="<?= e((string) ($server['default_max_tracks'] ?? old('default_max_tracks', '500'))) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label text-light fw-bold">Puerto SSH de Administración</label>
                    <input type="number" name="ssh_port" class="form-control bg-dark text-white border-secondary" required value="<?= e((string) ($server['ssh_port'] ?? old('ssh_port', '40002'))) ?>">
                    <div class="form-text text-muted">Puerto SSH del servidor (ej: 40002).</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-light fw-bold">Usuario SSH del Sistema</label>
                    <input type="text" name="ssh_user" class="form-control bg-dark text-white border-secondary" required value="<?= e($server['ssh_user'] ?? old('ssh_user', 'user')) ?>">
                    <div class="form-text text-muted">Usuario SSH con privilegios sudo (ej: user).</div>
                </div>

                <div class="col-12">
                    <label class="form-label text-light fw-bold">Notas de Configuración / Bitácora</label>
                    <textarea name="notes" class="form-control bg-dark text-white border-secondary" rows="2" placeholder="Notas internas sobre el hardware, proveedor de nube, licencias..."><?= e($server['notes'] ?? old('notes')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <button class="btn btn-info fw-bold text-dark px-4"><i class="bi bi-check-lg"></i> <?= $isEdit ? 'Guardar Cambios de Configuración' : 'Crear Servidor' ?></button>
    </div>
</form>
