<?php
$success = flash('success');
$error = flash('error');
$errors = flash('errors');
$warning = flash('warning');
$info = flash('info');
?>
<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <span><?= e($success) ?></span>
    <button type="button" class="alert-close" aria-label="Cerrar">&times;</button>
</div>
<?php endif; ?>
<?php if (is_array($errors) && $errors !== []): ?>
<div class="alert alert-error" role="alert">
    <strong>Corrija los siguientes errores:</strong>
    <ul class="alert-list">
        <?php foreach ($errors as $item): ?>
        <li><?= e((string) $item) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="alert-close" aria-label="Cerrar">&times;</button>
</div>
<?php elseif ($error): ?>
<div class="alert alert-error" role="alert">
    <span><?= e($error) ?></span>
    <button type="button" class="alert-close" aria-label="Cerrar">&times;</button>
</div>
<?php endif; ?>
<?php if ($warning): ?>
<div class="alert alert-warning" role="alert">
    <span><?= e($warning) ?></span>
    <button type="button" class="alert-close" aria-label="Cerrar">&times;</button>
</div>
<?php endif; ?>
<?php if ($info): ?>
<div class="alert alert-info" role="alert">
    <span><?= e($info) ?></span>
    <button type="button" class="alert-close" aria-label="Cerrar">&times;</button>
</div>
<?php endif; ?>
