<?php
$pageTitle = 'Nuevo plantel';
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/planteles') ?>">Planteles</a></li><li>/ Nuevo</li></ul>
        <h1 class="page-title">Registrar plantel</h1>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'planteles']); ?>

<div class="card">
    <div class="card-header">
        <h3>Datos del plantel</h3>
    </div>
    <form action="<?= url('catalogos/planteles') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="clave">Clave <span class="required">*</span></label>
                <input type="text" id="clave" name="clave" class="form-control" required maxlength="20"
                       placeholder="Ej. DG, LP, CAB" value="<?= e((string) old('clave')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="150"
                       placeholder="Ej. Dirección General" value="<?= e((string) old('nombre')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" checked> Plantel activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Registrar</button>
            <a href="<?= url('catalogos/planteles') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
