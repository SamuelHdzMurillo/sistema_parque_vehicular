<?php
$user = auth_user();
$pageTitle = $pageTitle ?? config('app', 'name');
$currentPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$basePath = trim(parse_url(url(''), PHP_URL_PATH), '/');
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
    $currentPath = trim(substr($currentPath, strlen($basePath)), '/');
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> — <?= e((string) config('app', 'name')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body>
<div class="sidebar-overlay" id="sidebar-overlay"></div>
<div class="app-shell">
    <?php App\Core\View::component('sidebar', ['currentPath' => $currentPath]); ?>

    <div class="main-wrapper" id="main-wrapper">
        <header class="topbar">
            <button type="button" class="topbar-toggle" data-sidebar-toggle aria-label="Menú">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>

            <form class="topbar-search" action="<?= url('busqueda') ?>" method="get" role="search">
                <span class="topbar-search-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                </span>
                <input type="search" id="global-search" name="q" placeholder="Buscar vehículos, comisiones, usuarios… (Ctrl+K)"
                       value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            </form>

            <div class="topbar-actions">
                <button type="button" class="btn-icon" data-theme-toggle aria-label="Cambiar tema"></button>

                <div class="user-menu">
                    <button type="button" class="user-menu-trigger" id="user-menu-trigger">
                        <span class="user-avatar"><?= e(mb_strtoupper(mb_substr($user['nombre'] ?? 'U', 0, 1))) ?></span>
                        <span><?= e(trim(($user['nombre'] ?? '') . ' ' . ($user['apellido_paterno'] ?? ''))) ?></span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                    <div class="user-dropdown" id="user-dropdown">
                        <div style="padding:.75rem 1rem;font-size:.8rem;color:var(--text-secondary)">
                            <?= e($user['email'] ?? '') ?>
                        </div>
                        <hr>
                        <a href="<?= url('change-password') ?>">Cambiar contraseña</a>
                        <hr>
                        <form action="<?= url('logout') ?>" method="post">
                            <?= csrf_field() ?>
                            <button type="submit">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <?php App\Core\View::component('flash'); ?>
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
