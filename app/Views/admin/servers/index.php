<?php /** @var array $servers */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Servidores de streaming</h5>
    <a href="<?= url('admin/servers/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nuevo servidor</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr><th>Nombre</th><th>Host</th><th>Driver</th><th>Rango puertos</th><th>Max streams</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($servers as $s): ?>
                <tr>
                    <td><i class="bi bi-hdd-rack"></i> <?= e($s['name']) ?></td>
                    <td><code><?= e($s['hostname']) ?></code></td>
                    <td><span class="badge bg-secondary text-uppercase"><?= e($s['driver']) ?></span></td>
                    <td><?= (int) $s['port_range_start'] ?>–<?= (int) $s['port_range_end'] ?></td>
                    <td><?= (int) $s['max_streams'] ?></td>
                    <td><?= status_badge($s['status']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('admin/servers/' . $s['id'] . '/edit') ?>" class="btn btn-sm btn-outline-light"><i class="bi bi-pencil"></i></a>
                        <form method="post" action="<?= url('admin/servers/' . $s['id']) ?>" class="d-inline" onsubmit="return confirm('Eliminar servidor?')">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="_method" value="DELETE">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p class="text-muted small mt-3"><i class="bi bi-info-circle"></i> El driver define como se controla Shoutcast: <strong>mock</strong> (simulado, para desarrollo), <strong>windows</strong> (sc_serv.exe local) o <strong>linux</strong> (systemd en produccion).</p>
