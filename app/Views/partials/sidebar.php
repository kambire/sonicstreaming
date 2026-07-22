<?php
/** @var array $user */
$role = $user['role'] ?? 'client';
$currentPath = request_path();
$baseRole = ($role === 'admin') ? 'admin' : (($role === 'reseller') ? 'reseller' : 'client');

$activeStationId = null;
$activeStation = null;
if (preg_match('#/(stations)/(\d+)#', $currentPath, $m)) {
    $activeStationId = (int) $m[2];
    $activeStation = \App\Models\Station::find($activeStationId);
}
?>
<aside class="sidebar">
    <!-- BRAND LOGO EMPRESARIAL -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-title">
            <i class="bi bi-broadcast text-primary"></i>
            <span><?= e(env('APP_NAME', 'SonicStreaming')) ?></span>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" style="font-size: 10px;">PRO v2.5</span>
    </div>

    <nav class="sidebar-nav">
        <!-- SECCIÓN DE NAVEGACIÓN GENERAL -->
        <div class="sidebar-heading">Menú Principal</div>

        <?php if ($role === 'admin'): ?>
            <a class="nav-link <?= request_path() === '/admin' || nav_active('/admin/dashboard') ? 'active' : '' ?>" href="<?= url('admin/dashboard') ?>">
                <i class="bi bi-grid-1x2-fill"></i> Panel de Control
            </a>
            <a class="nav-link <?= nav_active('/admin/stations') ?>" href="<?= url('admin/stations') ?>">
                <i class="bi bi-broadcast-pin"></i> Emisoras de Radio
            </a>
            <a class="nav-link <?= nav_active('/admin/users') ?>" href="<?= url('admin/users') ?>">
                <i class="bi bi-people-fill"></i> Clientes & Revendedores
            </a>
            <a class="nav-link <?= nav_active('/admin/plans') ?>" href="<?= url('admin/plans') ?>">
                <i class="bi bi-box-seam-fill"></i> Planes de Streaming
            </a>
            <a class="nav-link <?= nav_active('/admin/servers') ?>" href="<?= url('admin/servers') ?>">
                <i class="bi bi-hdd-rack-fill"></i> Servidores Dedicados
            </a>
            <a class="nav-link <?= nav_active('/admin/invoices') ?>" href="<?= url('admin/invoices') ?>">
                <i class="bi bi-receipt-cutoff"></i> Facturación
            </a>
        <?php elseif ($role === 'reseller'): ?>
            <a class="nav-link <?= nav_active('/reseller/dashboard') ?>" href="<?= url('reseller/dashboard') ?>">
                <i class="bi bi-grid-1x2-fill"></i> Panel Reseller
            </a>
            <a class="nav-link <?= nav_active('/reseller/stations') ?>" href="<?= url('reseller/stations') ?>">
                <i class="bi bi-broadcast-pin"></i> Mis Emisoras
            </a>
            <a class="nav-link <?= nav_active('/reseller/clients') ?>" href="<?= url('reseller/clients') ?>">
                <i class="bi bi-people-fill"></i> Mis Clientes
            </a>
        <?php else: ?>
            <a class="nav-link <?= nav_active('/client/dashboard') ?>" href="<?= url('client/dashboard') ?>">
                <i class="bi bi-grid-1x2-fill"></i> Mi Panel
            </a>
            <a class="nav-link <?= nav_active('/client/invoices') ?>" href="<?= url('client/invoices') ?>">
                <i class="bi bi-receipt-cutoff"></i> Facturación & Pagos
            </a>
        <?php endif; ?>

        <!-- SECCIÓN DE EMISORA ACTIVA SI SE ESTÁ GESTIONANDO UNA RADIO -->
        <?php if ($activeStation): ?>
            <div class="sidebar-heading mt-3 text-info">
                <i class="bi bi-radio me-1"></i> Radio: <?= e($activeStation['name']) ?>
            </div>

            <a class="nav-link <?= str_ends_with($currentPath, '/stations/' . $activeStationId) ? 'active' : '' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId) ?>">
                <i class="bi bi-sliders text-info"></i> Resumen & Control
            </a>

            <button type="button" class="nav-link border-0 bg-transparent text-danger text-start w-100" data-bs-toggle="modal" data-bs-target="#micStudioModal">
                <i class="bi bi-mic-fill text-danger"></i> Estudio "Hablar en Vivo"
            </button>

            <a class="nav-link <?= nav_active('/relay') ? 'active' : '' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/relay') ?>">
                <i class="bi bi-arrow-repeat text-success"></i> Re-transmisión & Relay
            </a>

            <a class="nav-link <?= nav_active('/analytics') ? 'active' : '' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/analytics') ?>">
                <i class="bi bi-globe-americas text-primary"></i> Audiencia & Mapa
            </a>

            <?php if (!empty($activeStation['autodj_enabled'])): ?>
                <!-- ACCORDION DE AUTODJ -->
                <div class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center <?= nav_active('/autodj') ? 'active' : '' ?>" data-bs-toggle="collapse" href="#autodjSubmenu" role="button" aria-expanded="<?= nav_active('/autodj') ? 'true' : 'false' ?>">
                        <span><i class="bi bi-music-note-list text-warning"></i> AutoDJ & Playlists</span>
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    
                    <div class="collapse <?= nav_active('/autodj') ? 'show' : '' ?> sidebar-submenu" id="autodjSubmenu">
                        <a class="nav-link <?= (nav_active('/autodj') && (str_contains($_SERVER['QUERY_STRING'] ?? '', 'tab=music') || empty($_GET['tab']))) ? 'active' : '' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/autodj?tab=music') ?>">
                            <i class="bi bi-music-note-beamer me-2"></i> Música & Generales
                        </a>
                        <a class="nav-link <?= (str_contains($_SERVER['QUERY_STRING'] ?? '', 'tab=jingles')) ? 'active' : '' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/autodj?tab=jingles') ?>">
                            <i class="bi bi-mic me-2"></i> Viñetas & Identificadores
                        </a>
                        <a class="nav-link <?= (str_contains($_SERVER['QUERY_STRING'] ?? '', 'tab=commercials')) ? 'active' : '' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/autodj?tab=commercials') ?>">
                            <i class="bi bi-badge-ad me-2"></i> Publicidad Rotativa
                        </a>
                        <a class="nav-link <?= (str_contains($_SERVER['QUERY_STRING'] ?? '', 'tab=exact_time')) ? 'active' : '' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/autodj?tab=exact_time') ?>">
                            <i class="bi bi-clock me-2"></i> Anuncio de Hora Exacta
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <!-- FOOTER DE SIDEBAR -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 13px;">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <div class="text-white text-truncate fw-bold small" style="max-width: 150px;"><?= e($user['name'] ?? '') ?></div>
                <div class="text-white-50 text-uppercase font-monospace" style="font-size: 10px;"><?= e($role) ?> Account</div>
            </div>
        </div>
    </div>
</aside>
