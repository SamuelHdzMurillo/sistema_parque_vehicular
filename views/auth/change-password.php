<?php $pageTitle = 'Cambiar contraseña'; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Cambiar contraseña</h1>
        <p class="page-subtitle">Actualice su contraseña de acceso al sistema</p>
    </div>
</div>

<div class="card" style="max-width:480px">
    <div class="card-body">
        <form action="<?= url('change-password') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="current_password">Contraseña actual <span class="required">*</span></label>
                <input type="password" id="current_password" name="current_password" class="form-control" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Nueva contraseña <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirmar nueva contraseña <span class="required">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
            </div>

            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="<?= url('dashboard') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
