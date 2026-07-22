<?php /** @var array $servers */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold text-white"><i class="bi bi-server text-info me-2"></i> Servidores & Configuración de Red</h4>
        <div class="small text-muted">Gestión de nodos, direcciones IP públicas, nombres de host FQDN, SSH y puertos de emisión.</div>
    </div>
    <a href="<?= url('admin/servers/create') ?>" class="btn btn-sm btn-info text-dark fw-bold shadow-sm"><i class="bi bi-plus-lg me-1"></i> Agregar Servidor</a>
</div>

<div class="card border-0 shadow-sm bg-dark bg-opacity-50">
    <div class="table-responsive">
        <table class="table table-dark table-hover mb-0 align-middle">
            <thead>
                <tr class="text-muted small border-bottom border-secondary border-opacity-25">
                    <th>Servidor / Nombre</th>
                    <th>Dominio FQDN & IP Pública</th>
                    <th>Puertos Panel / SSH</th>
                    <th>Motor Driver</th>
                    <th>Rango Puertos Stream</th>
                    <th>Máx Streams</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($servers as $s): ?>
                <tr>
                    <td>
                        <div class="fw-bold text-white"><i class="bi bi-hdd-rack text-info me-1"></i> <?= e($s['name']) ?></div>
                        <div class="small text-muted"><?= e($s['datacenter_location'] ?? 'DataCenter Default') ?></div>
                    </td>
                    <td>
                        <div class="font-monospace text-info fw-bold"><i class="bi bi-globe me-1"></i> <?= e($s['hostname']) ?></div>
                        <div class="small text-muted">IP: <code><?= e($s['public_ip'] ?? '127.0.0.1') ?></code></div>
                    </td>
                    <td>
                        <div class="small text-light">HTTPS: <code><?= (int) ($s['ssl_port'] ?? 7000) ?></code></div>
                        <div class="small text-muted">SSH: <code><?= (int) ($s['ssh_port'] ?? 40002) ?></code> (<?= e($s['ssh_user'] ?? 'user') ?>)</div>
                    </td>
                    <td><span class="badge bg-secondary text-uppercase"><?= e($s['driver']) ?></span></td>
                    <td><code class="text-success"><?= (int) $s['port_range_start'] ?>–<?= (int) $s['port_range_end'] ?></code></td>
                    <td><span class="fw-bold text-white"><?= (int) $s['max_streams'] ?></span></td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('admin/servers/' . $s['id'] . '/edit') ?>" class="btn btn-sm btn-outline-info me-1" title="Configurar Servidor"><i class="bi bi-gear-fill"></i> Configurar</a>
                        <form method="post" action="<?= url('admin/servers/' . $s['id']) ?>" class="d-inline" onsubmit="return confirm('¿Eliminar servidor?')">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
