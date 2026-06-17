<?php
$pageTitle = 'Editar conductor';
$conductor = $conductor ?? [];
$areas = $areas ?? [];
$c = array_merge($conductor, array_intersect_key($_SESSION['_old'] ?? [], array_flip(['nombre', 'area_id', 'telefono', 'activo'])));
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/conductores') ?>">Conductores</a></li><li>/ Editar</li></ul>
        <h1 class="page-title">Editar conductor</h1>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'conductores']); ?>

<div class="card">
    <div class="card-header">
        <h3><?= e($c['nombre']) ?></h3>
        <span class="text-muted"><?= e($c['telefono']) ?></span>
    </div>
    <form action="<?= url('catalogos/conductores/' . $c['id']) ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre completo <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="200" value="<?= e($c['nombre']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="area_id">Área <span class="required">*</span></label>
                <select id="area_id" name="area_id" class="form-select" required>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (int) $c['area_id'] === (int) $a['id'] ? 'selected' : '' ?>>
                        <?= e($a['label'] ?? catalogo_area_label($a)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="telefono">Teléfono <span class="required">*</span></label>
                <input type="text" id="telefono" name="telefono" class="form-control" required maxlength="20" value="<?= e($c['telefono']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" <?= (int) ($c['activo'] ?? 1) === 1 ? 'checked' : '' ?>> Conductor activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('catalogos/conductores') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
