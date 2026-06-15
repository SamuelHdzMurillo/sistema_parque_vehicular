<?php $pageTitle = 'Restablecer contraseña'; ?>
<p class="text-muted mb-2">Establezca una nueva contraseña segura para su cuenta.</p>

<form action="<?= url('reset-password') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

    <div class="form-group">
        <label class="form-label" for="password">Nueva contraseña <span class="required">*</span></label>
        <input type="password" id="password" name="password" class="form-control" required minlength="8"
               autocomplete="new-password" placeholder="Mínimo 8 caracteres">
        <p class="form-hint">Use mayúsculas, minúsculas, números y símbolos.</p>
    </div>

    <div class="form-group">
        <label class="form-label" for="password_confirmation">Confirmar contraseña <span class="required">*</span></label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
               required minlength="8" autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary w-100">Restablecer contraseña</button>

    <p class="text-center mt-2 mb-0">
        <a href="<?= url('login') ?>">&larr; Volver al inicio de sesión</a>
    </p>
</form>
