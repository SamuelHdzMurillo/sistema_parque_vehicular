<?php
$success = flash('success');
$error = flash('error');
$warning = flash('warning');
$info = flash('info');
?>
<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <span><?= e($success) ?></span>
    <button type="button" class="alert-close" aria-label="Cerrar">&times;</button>
</div>
<?php endif; ?>
<?php if ($error): ?>
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
