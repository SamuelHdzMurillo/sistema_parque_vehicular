<?php
$pageTitle = 'Nuevo conductor';
$areas = $areas ?? [];
$planteles = $planteles ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/conductores') ?>">Conductores</a></li><li>/ Nuevo</li></ul>
        <h1 class="page-title">Registrar conductor</h1>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'conductores']); ?>

<div class="card">
    <div class="card-header">
        <h3>Datos del conductor</h3>
    </div>
    <form action="<?= url('catalogos/conductores') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre completo <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="200"
                       value="<?= e((string) old('nombre')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="area_id">Área <span class="required">*</span></label>
                <div class="input-group">
                    <select id="area_id" name="area_id" class="form-select" required data-conductor-area-select>
                        <option value="">Seleccione…</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= (int) $a['id'] ?>" <?= (string) old('area_id') === (string) $a['id'] ? 'selected' : '' ?>>
                            <?= e($a['label'] ?? catalogo_area_label($a)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('catalogos.create')): ?>
                    <button type="button" class="btn btn-accent" data-area-quick-open title="Agregar área" aria-label="Agregar área">+</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono <span class="required">*</span></label>
                <input type="text" id="telefono" name="telefono" class="form-control" required maxlength="20"
                       placeholder="Ej. 6121234567" value="<?= e((string) old('telefono')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" checked> Conductor activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Registrar</button>
            <a href="<?= url('catalogos/conductores') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php if (can('catalogos.create')): ?>
<?php App\Core\View::component('modal-area-quick', ['planteles' => $planteles]); ?>
<?php App\Core\View::component('modal-plantel-quick'); ?>
<?php endif; ?>
