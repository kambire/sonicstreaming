<?php
/** @var array $user */
$role = $user['role'] ?? 'client';
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-broadcast"></i>
        <span><?= e(env('APP_NAME', 'SonicStreaming')) ?></span>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <a class="nav-link <?= request_path() === '/admin' || nav_active('/admin/dashboard') ? 'active' : '' ?>" href="<?= url('admin/dashboard') ?>">
                <i class="bi bi-speedometer2"></i> Panel
            </a>
            <a class="nav-link <?= nav_active('/admin/stations') ?>" href="<?= url('admin/stations') ?>">
                <i class="bi bi-broadcast-pin"></i> Estaciones
            </a>
            <a class="nav-link <?= nav_active('/admin/users') ?>" href="<?= url('admin/users') ?>">
                <i class="bi bi-people"></i> Clientes / Resellers
            </a>
            <a class="nav-link <?= nav_active('/admin/plans') ?>" href="<?= url('admin/plans') ?>">
                <i class="bi bi-box-seam"></i> Planes
            </a>
            <a class="nav-link <?= nav_active('/admin/servers') ?>" href="<?= url('admin/servers') ?>">
                <i class="bi bi-hdd-rack"></i> Servidores
            </a>
            <a class="nav-link <?= nav_active('/admin/invoices') ?>" href="<?= url('admin/invoices') ?>">
                <i class="bi bi-receipt"></i> Facturas
            </a>
        <?php elseif ($role === 'reseller'): ?>
            <a class="nav-link <?= nav_active('/reseller/dashboard') ?>" href="<?= url('reseller/dashboard') ?>">
                <i class="bi bi-speedometer2"></i> Panel
            </a>
            <a class="nav-link <?= nav_active('/reseller/stations') ?>" href="<?= url('reseller/stations') ?>">
                <i class="bi bi-broadcast-pin"></i> Estaciones
            </a>
            <a class="nav-link <?= nav_active('/reseller/clients') ?>" href="<?= url('reseller/clients') ?>">
                <i class="bi bi-people"></i> Mis clientes
            </a>
        <?php else: ?>
            <a class="nav-link <?= nav_active('/client/dashboard') ?>" href="<?= url('client/dashboard') ?>">
                <i class="bi bi-speedometer2"></i> Mi panel
            </a>
            <a class="nav-link <?= nav_active('/client/invoices') ?>" href="<?= url('client/invoices') ?>">
                <i class="bi bi-receipt"></i> Mis facturas
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer small text-muted">
        v1.0 · <?= e(ucfirst($role)) ?>
    </div>
</aside>
