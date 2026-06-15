<?php $pageTitle = 'Recuperar contraseña'; ?>
<p class="text-muted mb-2">Ingrese su correo institucional. Si existe una cuenta asociada, recibirá instrucciones para restablecer su contraseña.</p>

<form action="<?= url('forgot-password') ?>" method="post">
    <?= csrf_field() ?>

    <div class="form-group">
        <label class="form-label" for="email">Correo electrónico <span class="required">*</span></label>
        <input type="email" id="email" name="email" class="form-control" required autofocus
               value="<?= e((string) old('email')) ?>" placeholder="usuario@cecyte.edu.mx">
    </div>

    <button type="submit" class="btn btn-primary w-100">Enviar enlace de recuperación</button>

    <p class="text-center mt-2 mb-0">
        <a href="<?= url('login') ?>">&larr; Volver al inicio de sesión</a>
    </p>
</form>
