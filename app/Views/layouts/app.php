<?php
/** @var string $content */
$title = $title ?? env('APP_NAME', 'SonicStreaming');
$user = auth();
?>
<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e(env('APP_NAME', 'SonicStreaming')) ?></title>
    <link href="<?= asset('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/bootstrap-icons.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?= \App\Core\View::renderPartial('partials/sidebar', ['user' => $user]) ?>

    <div class="app-main">
        <nav class="topbar">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-title"><?= e($title) ?></div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-5 me-2"></i>
                    <span class="d-none d-sm-inline"><?= e($user['name'] ?? '') ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= e($user['email'] ?? '') ?></span></li>
                    <li><span class="dropdown-item-text small text-uppercase text-info"><?= e($user['role'] ?? '') ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= url('profile') ?>"><i class="bi bi-person-gear"></i> Mi perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= url('logout') ?>" class="px-2">
                            <?= \App\Core\Csrf::field() ?>
                            <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-box-arrow-right"></i> Salir</button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="app-content">
            <?= \App\Core\View::renderPartial('partials/flash') ?>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  window.APP_BASE = <?= json_encode(base_url()) ?>;
  document.getElementById('sidebarToggle')?.addEventListener('click', function () {
    document.querySelector('.sidebar')?.classList.toggle('open');
  });
</script>
<?php if (!empty($scripts)): ?><?= $scripts ?><?php endif; ?>
</body>
</html>
