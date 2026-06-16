<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle ?? 'Acceso') ?> — <?= e((string) config('app', 'name')) ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body>
<div class="guest-layout">
    <div class="guest-card">
        <div class="guest-brand">
            <?= brand_logo_img('guest-brand-logo') ?>
            <h1><?= e((string) config('app', 'name')) ?></h1>
            <p>Panel de administración</p>
        </div>
        <?php App\Core\View::component('flash'); ?>
        <?= $content ?>
    </div>
</div>
</body>
</html>
