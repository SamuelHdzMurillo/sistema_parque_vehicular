<?php
$pageTitle = 'Editar área';
$area = $area ?? [];
$planteles = $planteles ?? [];
$a = array_merge($area, array_intersect_key($_SESSION['_old'] ?? [], array_flip(['clave', 'nombre', 'plantel_id', 'activo'])));
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/areas') ?>">Áreas</a></li><li>/ Editar</li></ul>
        <h1 class="page-title">Editar área</h1>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'areas']); ?>

<div class="card">
    <div class="card-header">
        <h3><?= e($a['nombre']) ?></h3>
        <span class="badge badge-secondary"><?= e($a['clave']) ?></span>
    </div>
    <form action="<?= url('catalogos/areas/' . $a['id']) ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="clave">Clave <span class="required">*</span></label>
                <input type="text" id="clave" name="clave" class="form-control" required maxlength="20" value="<?= e($a['clave']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre del área <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="150" value="<?= e($a['nombre']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="plantel_id">Plantel <span class="required">*</span></label>
                <select id="plantel_id" name="plantel_id" class="form-select" required>
                    <?php foreach ($planteles as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) $a['plantel_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['clave'] . ' — ' . $p['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" <?= (int) ($a['activo'] ?? 1) === 1 ? 'checked' : '' ?>> Área activa
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('catalogos/areas') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
