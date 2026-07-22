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
    <div class="sidebar-brand">
        <i class="bi bi-broadcast text-info fs-4"></i>
        <span class="fw-bold text-white"><?= e(env('APP_NAME', 'SonicStreaming')) ?></span>
    </div>

    <nav class="sidebar-nav">
        <!-- MENÚ GENERAL -->
        <?php if ($role === 'admin'): ?>
            <a class="nav-link <?= request_path() === '/admin' || nav_active('/admin/dashboard') ? 'active' : '' ?>" href="<?= url('admin/dashboard') ?>">
                <i class="bi bi-speedometer2 me-2"></i> Panel Principal
            </a>
            <a class="nav-link <?= nav_active('/admin/stations') ?>" href="<?= url('admin/stations') ?>">
                <i class="bi bi-broadcast-pin me-2"></i> Estaciones
            </a>
            <a class="nav-link <?= nav_active('/admin/users') ?>" href="<?= url('admin/users') ?>">
                <i class="bi bi-people me-2"></i> Clientes / Resellers
            </a>
            <a class="nav-link <?= nav_active('/admin/plans') ?>" href="<?= url('admin/plans') ?>">
                <i class="bi bi-box-seam me-2"></i> Planes
            </a>
            <a class="nav-link <?= nav_active('/admin/servers') ?>" href="<?= url('admin/servers') ?>">
                <i class="bi bi-hdd-rack me-2"></i> Servidores
            </a>
            <a class="nav-link <?= nav_active('/admin/invoices') ?>" href="<?= url('admin/invoices') ?>">
                <i class="bi bi-receipt me-2"></i> Facturas
            </a>
        <?php elseif ($role === 'reseller'): ?>
            <a class="nav-link <?= nav_active('/reseller/dashboard') ?>" href="<?= url('reseller/dashboard') ?>">
                <i class="bi bi-speedometer2 me-2"></i> Mi Panel
            </a>
            <a class="nav-link <?= nav_active('/reseller/stations') ?>" href="<?= url('reseller/stations') ?>">
                <i class="bi bi-broadcast-pin me-2"></i> Mis Estaciones
            </a>
            <a class="nav-link <?= nav_active('/reseller/clients') ?>" href="<?= url('reseller/clients') ?>">
                <i class="bi bi-people me-2"></i> Mis Clientes
            </a>
        <?php else: ?>
            <a class="nav-link <?= nav_active('/client/dashboard') ?>" href="<?= url('client/dashboard') ?>">
                <i class="bi bi-speedometer2 me-2"></i> Mi Panel
            </a>
            <a class="nav-link <?= nav_active('/client/invoices') ?>" href="<?= url('client/invoices') ?>">
                <i class="bi bi-receipt me-2"></i> Mis Facturas
            </a>
        <?php endif; ?>

        <!-- SUBMENÚ DESPLEGABLE DE ESTACIÓN ACTIVA SI SE ESTÁ GESTIONANDO UNA RADIO -->
        <?php if ($activeStation): ?>
            <div class="sidebar-heading mt-3 mb-1 px-3 text-uppercase text-info font-monospace fw-bold" style="font-size:11px;">
                <i class="bi bi-radio me-1"></i> Radio Actual: <?= e($activeStation['name']) ?>
            </div>

            <a class="nav-link ps-4 <?= str_ends_with($currentPath, '/stations/' . $activeStationId) ? 'active' : '' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId) ?>">
                <i class="bi bi-sliders me-2 text-info"></i> Resumen & Estado
            </a>

            <button type="button" class="nav-link ps-4 w-100 text-start border-0 bg-transparent text-danger fw-bold" data-bs-toggle="modal" data-bs-target="#micStudioModal">
                <i class="bi bi-mic-fill me-2"></i> 🎙️ Hablar en Vivo
            </button>

            <a class="nav-link ps-4 <?= nav_active('/analytics') ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/analytics') ?>">
                <i class="bi bi-globe-americas me-2 text-info"></i> Analíticas & Mapa
            </a>

            <?php if (!empty($activeStation['autodj_enabled'])): ?>
                <!-- DESPLEGABLE ACCORDION DE AUTODJ EN MENÚ LATERAL -->
                <div class="nav-item">
                    <a class="nav-link ps-4 d-flex justify-content-between align-items-center <?= nav_active('/autodj') ? 'active' : '' ?>" data-bs-toggle="collapse" href="#autodjSubmenu" role="button" aria-expanded="<?= nav_active('/autodj') ? 'true' : 'false' ?>">
                        <span><i class="bi bi-music-note-list me-2 text-warning"></i> AutoDJ & Playlists</span>
                        <i class="bi bi-chevron-down small"></i>
                    </a>
                    
                    <div class="collapse <?= nav_active('/autodj') ? 'show' : '' ?> ps-3" id="autodjSubmenu">
                        <a class="nav-link small py-1 <?= (nav_active('/autodj') && (str_contains($_SERVER['QUERY_STRING'] ?? '', 'tab=music') || empty($_GET['tab']))) ? 'text-info fw-bold' : 'text-muted' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/autodj?tab=music') ?>">
                            <i class="bi bi-music-note-beamer me-2"></i> 1. Música & Generales
                        </a>
                        <a class="nav-link small py-1 <?= (str_contains($_SERVER['QUERY_STRING'] ?? '', 'tab=jingles')) ? 'text-info fw-bold' : 'text-muted' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/autodj?tab=jingles') ?>">
                            <i class="bi bi-mic me-2"></i> 2. Viñetas & Separadores
                        </a>
                        <a class="nav-link small py-1 <?= (str_contains($_SERVER['QUERY_STRING'] ?? '', 'tab=commercials')) ? 'text-info fw-bold' : 'text-muted' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/autodj?tab=commercials') ?>">
                            <i class="bi bi-badge-ad me-2"></i> 3. Publicidad Rotativa
                        </a>
                        <a class="nav-link small py-1 <?= (str_contains($_SERVER['QUERY_STRING'] ?? '', 'tab=exact_time')) ? 'text-info fw-bold' : 'text-muted' ?>" href="<?= url($baseRole . '/stations/' . $activeStationId . '/autodj?tab=exact_time') ?>">
                            <i class="bi bi-clock me-2"></i> 4. Hora Exacta
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer small text-muted">
        SonicStreaming v1.0 · <?= e(ucfirst($role)) ?>
    </div>
</aside>
