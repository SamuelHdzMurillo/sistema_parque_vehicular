<?php
$pageTitle = 'Editar proveedor';
$p = $proveedor ?? [];
$tipos = $tipos ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('proveedores') ?>">Proveedores</a></li>
            <li>/ Editar</li>
        </ul>
        <h1 class="page-title">Editar <?= e($p['razon_social'] ?? '') ?></h1>
    </div>
</div>
<div class="card">
    <form action="<?= url('proveedores/' . $p['id']) ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="razon_social">Razón social <span class="required">*</span></label>
                <input type="text" id="razon_social" name="razon_social" class="form-control" required value="<?= e((string) ($p['razon_social'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="rfc">RFC</label>
                <input type="text" id="rfc" name="rfc" class="form-control" maxlength="13" value="<?= e((string) ($p['rfc'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="tipo">Tipo de proveedor</label>
                <select id="tipo" name="tipo" class="form-select">
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= e($t) ?>" <?= ($p['tipo'] ?? 'ambos') === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" class="form-control" maxlength="20" value="<?= e((string) ($p['telefono'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" maxlength="150" value="<?= e((string) ($p['email'] ?? '')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="direccion">Dirección</label>
            <textarea id="direccion" name="direccion" class="form-textarea"><?= e((string) ($p['direccion'] ?? '')) ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" <?= (int) ($p['activo'] ?? 1) === 1 ? 'checked' : '' ?>> Proveedor activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('proveedores') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
