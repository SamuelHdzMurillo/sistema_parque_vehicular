<?php
$pageTitle = 'Nuevo usuario';
$roles = $roles ?? [];
$areas = $areas ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('usuarios') ?>">Usuarios</a></li><li>/ Nuevo</li></ul>
        <h1 class="page-title">Registrar usuario</h1>
    </div>
</div>
<div class="card">
    <form action="<?= url('usuarios') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre(s) <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required value="<?= e((string) old('nombre')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="apellido_paterno">Apellido paterno <span class="required">*</span></label>
                <input type="text" id="apellido_paterno" name="apellido_paterno" class="form-control" required value="<?= e((string) old('apellido_paterno')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="apellido_materno">Apellido materno</label>
                <input type="text" id="apellido_materno" name="apellido_materno" class="form-control" value="<?= e((string) old('apellido_materno')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Correo electrónico <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= e((string) old('email')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" class="form-control" value="<?= e((string) old('telefono')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="role_id">Rol <span class="required">*</span></label>
                <select id="role_id" name="role_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" data-slug="<?= e((string) ($r['slug'] ?? '')) ?>" data-descripcion="<?= e((string) ($r['descripcion'] ?? '')) ?>" <?= (string) old('role_id') === (string) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php App\Core\View::component('rol-ayuda-select'); ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="area_id">Área</label>
                <select id="area_id" name="area_id" class="form-select">
                    <option value="">— Sin área —</option>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= (int) $a['id'] ?>"><?= e($a['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Contraseña <span class="required">*</span></label>
                <input type="password" id="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirmar contraseña <span class="required">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required minlength="8">
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" checked>
                Usuario activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Crear usuario</button>
            <a href="<?= url('usuarios') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
