<?php
/** @var array|null $station; @var array $clients; @var array $servers; @var array $plans */
$isEdit = $station !== null;
$action = $isEdit ? url('admin/stations/' . $station['id']) : url('admin/stations');
$type   = $station['type'] ?? old('type', 'live');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><?= $isEdit ? 'Editar estacion' : 'Nueva estacion' ?></h5>
    <a href="<?= url('admin/stations') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
</div>

<div class="card"><div class="card-body">
    <form method="post" action="<?= $action ?>">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre de la estacion</label>
                <input type="text" name="name" class="form-control" required value="<?= e($station['name'] ?? old('name')) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Genero</label>
                <input type="text" name="genre" class="form-control" value="<?= e($station['genre'] ?? old('genre')) ?>">
            </div>

            <?php if (!$isEdit): ?>
                <div class="col-md-6">
                    <label class="form-label">Cliente dueno</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">— Selecciona —</option>
                        <?php foreach ($clients as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= old('user_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= e($c['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Servidor</label>
                    <select name="server_id" class="form-select" required>
                        <?php foreach ($servers as $sv): ?>
                            <option value="<?= (int) $sv['id'] ?>"><?= e($sv['name']) ?> (Rango: <?= (int) $sv['port_range_start'] ?>–<?= (int) $sv['port_range_end'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Puerto de emisión <span class="text-muted small">(opcional, vacío = automático según rango)</span></label>
                    <input type="number" name="port" class="form-control" placeholder="Ej: 8000" value="<?= e((string) old('port')) ?>">
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <label class="form-label">Puerto asignado</label>
                    <input type="number" name="port" class="form-control" value="<?= (int) $station['port'] ?>">
                    <div class="form-text">Puedes modificar el puerto manualmente si la estación está detenida.</div>
                </div>
            <?php endif; ?>

            <div class="col-md-6">
                <label class="form-label">Plan</label>
                <select name="plan_id" class="form-select" id="planSelect">
                    <option value="" data-l="" data-b="">— Sin plan —</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"
                                data-l="<?= (int) $p['max_listeners'] ?>" data-b="<?= (int) $p['max_bitrate'] ?>"
                                <?= (string) ($station['plan_id'] ?? old('plan_id')) === (string) $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?> · <?= (int) $p['max_bitrate'] ?>k · <?= (int) $p['max_listeners'] ?> oy.
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Oyentes maximos</label>
                <input type="number" name="max_listeners" id="maxListeners" class="form-control" min="1"
                       value="<?= e((string) ($station['max_listeners'] ?? old('max_listeners', '100'))) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Bitrate maximo (kbps)</label>
                <input type="number" name="max_bitrate" id="maxBitrate" class="form-control" min="8" step="8"
                       value="<?= e((string) ($station['max_bitrate'] ?? old('max_bitrate', '128'))) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Tipo de fuente</label>
                <select name="type" class="form-select" id="typeSelect">
                    <option value="live"  <?= $type === 'live' ? 'selected' : '' ?>>En vivo / AutoDJ</option>
                    <option value="relay" <?= $type === 'relay' ? 'selected' : '' ?>>Relay (retransmitir otro stream)</option>
                </select>
            </div>
            <div class="col-md-6 relay-field">
                <label class="form-label">URL del relay</label>
                <input type="text" name="relay_url" class="form-control" placeholder="http://host:puerto/stream"
                       value="<?= e($station['relay_url'] ?? old('relay_url')) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Contrasena de fuente (source) <?= $isEdit ? '<span class="text-muted small">(vacio = no cambiar)</span>' : '<span class="text-muted small">(vacio = generar)</span>' ?></label>
                <input type="text" name="source_password" class="form-control" value="">
            </div>
            <div class="col-md-6">
                <label class="form-label">Contrasena admin <?= $isEdit ? '<span class="text-muted small">(vacio = no cambiar)</span>' : '<span class="text-muted small">(vacio = generar)</span>' ?></label>
                <input type="text" name="admin_password" class="form-control" value="">
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="autodj_enabled" id="autodj" value="1"
                        <?= (($station['autodj_enabled'] ?? old('autodj_enabled')) ? 'checked' : '') ?>>
                    <label class="form-check-label" for="autodj">Habilitar AutoDJ (Liquidsoap) para esta estacion</label>
                </div>
            </div>
        </div>

        <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
    </form>
</div></div>

<script>
(function () {
    const typeSel = document.getElementById('typeSelect');
    function toggleRelay() {
        document.querySelectorAll('.relay-field').forEach(el => el.style.display = typeSel.value === 'relay' ? '' : 'none');
    }
    typeSel.addEventListener('change', toggleRelay); toggleRelay();

    const plan = document.getElementById('planSelect');
    plan.addEventListener('change', function () {
        const opt = plan.options[plan.selectedIndex];
        if (opt.dataset.l) document.getElementById('maxListeners').value = opt.dataset.l;
        if (opt.dataset.b) document.getElementById('maxBitrate').value = opt.dataset.b;
    });
})();
</script>
