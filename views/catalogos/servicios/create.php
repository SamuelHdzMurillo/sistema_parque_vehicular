<?php
$pageTitle = 'Nuevo servicio';
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/servicios') ?>">Servicios</a></li><li>/ Nuevo</li></ul>
        <h1 class="page-title">Registrar servicio</h1>
        <p class="page-subtitle">Aparecerá al registrar mantenimientos preventivos</p>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'servicios']); ?>

<div class="card">
    <div class="card-header">
        <h3>Datos del servicio</h3>
    </div>
    <form action="<?= url('catalogos/servicios') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre del servicio <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="100"
                       placeholder="Ej. Cambio de aceite" value="<?= e((string) old('nombre')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="tipo">Código interno</label>
                <input type="text" id="tipo" name="tipo" class="form-control" maxlength="50"
                       placeholder="Ej. cambio_aceite (opcional, se genera del nombre)"
                       value="<?= e((string) old('tipo')) ?>">
                <small class="form-hint text-muted">Letras minúsculas, números y guión bajo. No se puede cambiar después.</small>
            </div>
        </div>

        <div class="form-group mt-2">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" checked> Servicio activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Registrar</button>
            <a href="<?= url('catalogos/servicios') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
