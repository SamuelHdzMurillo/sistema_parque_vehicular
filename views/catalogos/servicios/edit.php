<?php
$pageTitle = 'Editar servicio';
$servicio = $servicio ?? [];
$s = array_merge($servicio, array_intersect_key($_SESSION['_old'] ?? [], array_flip(['nombre', 'activo'])));
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/servicios') ?>">Servicios</a></li><li>/ Editar</li></ul>
        <h1 class="page-title">Editar servicio</h1>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'servicios']); ?>

<div class="card">
    <div class="card-header">
        <h3><?= e($s['nombre']) ?></h3>
        <span class="badge badge-secondary"><code><?= e($s['tipo']) ?></code></span>
    </div>
    <form action="<?= url('catalogos/servicios/' . $s['id']) ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="nombre">Nombre del servicio <span class="required">*</span></label>
            <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="100" value="<?= e($s['nombre']) ?>">
        </div>

        <div class="form-group mt-2">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" <?= (int) ($s['activo'] ?? 1) === 1 ? 'checked' : '' ?>> Servicio activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('catalogos/servicios') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
