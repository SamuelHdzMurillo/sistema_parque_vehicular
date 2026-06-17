<?php
$pageTitle = 'Editar usuario';
$usuario = $usuario ?? [];
$roles = $roles ?? [];
$areas = $areas ?? [];
$u = $usuario;
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('usuarios') ?>">Usuarios</a></li><li>/ Editar</li></ul>
        <h1 class="page-title">Editar usuario</h1>
        <p class="page-subtitle"><?= e($u['email'] ?? '') ?></p>
    </div>
</div>
<div class="card">
    <form action="<?= url('usuarios/' . $u['id']) ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre(s)</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required value="<?= e($u['nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="apellido_paterno">Apellido paterno</label>
                <input type="text" id="apellido_paterno" name="apellido_paterno" class="form-control" required value="<?= e($u['apellido_paterno'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="apellido_materno">Apellido materno</label>
                <input type="text" id="apellido_materno" name="apellido_materno" class="form-control" value="<?= e($u['apellido_materno'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Correo</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?= e($u['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" class="form-control" value="<?= e($u['telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="role_id">Rol</label>
                <select id="role_id" name="role_id" class="form-select" required>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" data-slug="<?= e((string) ($r['slug'] ?? '')) ?>" data-descripcion="<?= e((string) ($r['descripcion'] ?? '')) ?>" <?= (int) ($u['role_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php App\Core\View::component('rol-ayuda-select'); ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="area_id">Área</label>
                <select id="area_id" name="area_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (int) ($u['area_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>><?= e(catalogo_area_label($a)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" <?= !empty($u['activo']) ? 'checked' : '' ?>>
                Usuario activo
            </label>
        </div>
        <hr style="border:none;border-top:1px solid var(--border-color);margin:1.5rem 0">
        <p class="text-muted mb-2">Deje en blanco para mantener la contraseña actual.</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="password">Nueva contraseña</label>
                <input type="password" id="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" minlength="8">
            </div>
        </div>
        <div class="d-flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="<?= url('usuarios') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
