<?php
$pageTitle = 'Nueva área';
$planteles = $planteles ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/areas') ?>">Áreas</a></li><li>/ Nueva</li></ul>
        <h1 class="page-title">Registrar área</h1>
        <p class="page-subtitle">Se mostrará en comisiones como <em>Nombre - Clave del plantel</em></p>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'areas']); ?>

<div class="card">
    <div class="card-header">
        <h3>Datos del área</h3>
    </div>
    <form action="<?= url('catalogos/areas') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="clave">Clave <span class="required">*</span></label>
                <input type="text" id="clave" name="clave" class="form-control" required maxlength="20"
                       placeholder="Ej. JUR" value="<?= e((string) old('clave')) ?>">
                <small class="form-hint text-muted">Se guardará en mayúsculas.</small>
            </div>
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre del área <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="150"
                       placeholder="Ej. Jurídico" value="<?= e((string) old('nombre')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="plantel_id">Plantel <span class="required">*</span></label>
                <div class="input-group">
                    <select id="plantel_id" name="plantel_id" class="form-select" required data-plantel-select>
                        <option value="">Seleccione…</option>
                        <?php foreach ($planteles as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (string) old('plantel_id') === (string) $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['clave'] . ' — ' . $p['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('catalogos.create')): ?>
                    <button type="button" class="btn btn-accent" data-plantel-quick-open title="Agregar plantel" aria-label="Agregar plantel">+</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" checked> Área activa
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Registrar</button>
            <a href="<?= url('catalogos/areas') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php if (can('catalogos.create')): ?>
<?php App\Core\View::component('modal-plantel-quick'); ?>
<?php endif; ?>
