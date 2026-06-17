<?php
$pageTitle = 'Subir documento';
$vehiculos = $vehiculos ?? [];
$preVehiculo = $_GET['vehiculo_id'] ?? old('vehiculo_id');
$tipos = ['poliza_seguro','verificacion','tarjeta_circulacion','factura','tenencia','otro'];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('documentos') ?>">Documentos</a></li><li>/ Subir</li></ul>
        <h1 class="page-title">Subir documento</h1>
    </div>
</div>
<div class="card">
    <form action="<?= url('documentos') ?>" method="post" enctype="multipart/form-data" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="vehiculo_id">Vehículo <span class="required">*</span></label>
                <select id="vehiculo_id" name="vehiculo_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($vehiculos as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= (string) $preVehiculo === (string) $v['id'] ? 'selected' : '' ?>>
                        <?= e(catalogo_vehiculo_label($v)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="tipo">Tipo <span class="required">*</span></label>
                <select id="tipo" name="tipo" class="form-select" required>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= e($t) ?>"><?= e(ucfirst(str_replace('_', ' ', $t))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="titulo">Título <span class="required">*</span></label>
                <input type="text" id="titulo" name="titulo" class="form-control" required value="<?= e((string) old('titulo')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="numero_documento">No. documento</label>
                <input type="text" id="numero_documento" name="numero_documento" class="form-control" value="<?= e((string) old('numero_documento')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha_vencimiento">Fecha vencimiento</label>
                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="form-control" value="<?= e((string) old('fecha_vencimiento')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="version">Versión</label>
                <input type="text" id="version" name="version" class="form-control" value="<?= e((string) old('version', '1')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="archivo">Archivo (PDF, XML, imagen) <span class="required">*</span></label>
            <input type="file" id="archivo" name="archivo" class="form-control" required accept=".pdf,.xml,.jpg,.jpeg,.png">
            <p class="form-hint">Tamaño máximo: 5 MB</p>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Subir documento</button>
            <a href="<?= url('documentos') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
