<?php $pageTitle = 'Iniciar sesión'; ?>
<form action="<?= url('login') ?>" method="post" class="auth-form">
    <?= csrf_field() ?>

    <div class="form-group">
        <label class="form-label" for="email">Correo electrónico <span class="required">*</span></label>
        <input type="email" id="email" name="email" class="form-control" required autofocus
               value="<?= e((string) old('email')) ?>" placeholder="usuario@cecyte.edu.mx">
    </div>

    <div class="form-group">
        <label class="form-label" for="password">Contraseña <span class="required">*</span></label>
        <input type="password" id="password" name="password" class="form-control" required
               placeholder="••••••••">
    </div>

    <div class="form-group">
        <label class="form-check">
            <input type="checkbox" name="remember" value="1" <?= old('remember') ? 'checked' : '' ?>>
            Recordar sesión en este equipo
        </label>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-100">Entrar</button>

    <p class="text-center mt-2 mb-0">
        <a href="<?= url('forgot-password') ?>">¿Olvidó su contraseña?</a>
    </p>
</form>
