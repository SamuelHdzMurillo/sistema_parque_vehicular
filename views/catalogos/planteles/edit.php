<?php
$pageTitle = 'Editar plantel';
$plantel = $plantel ?? [];
$p = array_merge($plantel, array_intersect_key($_SESSION['_old'] ?? [], array_flip(['clave', 'nombre', 'activo'])));
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/planteles') ?>">Planteles</a></li><li>/ Editar</li></ul>
        <h1 class="page-title">Editar plantel</h1>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'planteles']); ?>

<div class="card">
    <div class="card-header">
        <h3><?= e($p['nombre']) ?></h3>
        <span class="badge badge-info"><?= e($p['clave']) ?></span>
    </div>
    <form action="<?= url('catalogos/planteles/' . $p['id']) ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="clave">Clave <span class="required">*</span></label>
                <input type="text" id="clave" name="clave" class="form-control" required maxlength="20" value="<?= e($p['clave']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="150" value="<?= e($p['nombre']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" <?= (int) ($p['activo'] ?? 1) === 1 ? 'checked' : '' ?>> Plantel activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('catalogos/planteles') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
