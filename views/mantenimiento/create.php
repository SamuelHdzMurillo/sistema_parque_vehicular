<?php
$pageTitle = 'Nuevo mantenimiento';
$vehiculos = $vehiculos ?? [];
$proveedores = $proveedores ?? [];
$responsables = $responsables ?? [];
$tipos = $tipos ?? [];
$preVehiculo = $_GET['vehiculo_id'] ?? old('vehiculo_id');
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('mantenimiento') ?>">Mantenimiento</a></li><li>/ Nuevo</li></ul>
        <h1 class="page-title">Registrar mantenimiento</h1>
    </div>
</div>
<div class="card">
    <form action="<?= url('mantenimiento') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="vehiculo_id">Vehículo <span class="required">*</span></label>
                <select id="vehiculo_id" name="vehiculo_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($vehiculos as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= (string) $preVehiculo === (string) $v['id'] ? 'selected' : '' ?>><?= e($v['numero_economico']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="tipo">Tipo <span class="required">*</span></label>
                <select id="tipo" name="tipo" class="form-select" required>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= e($t) ?>"><?= e(ucfirst($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha <span class="required">*</span></label>
                <input type="date" id="fecha" name="fecha" class="form-control" required value="<?= e((string) old('fecha', date('Y-m-d'))) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="kilometraje">Kilometraje <span class="required">*</span></label>
                <input type="number" id="kilometraje" name="kilometraje" class="form-control" required min="0" value="<?= e((string) old('kilometraje')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="proveedor_id">Proveedor</label>
                <select id="proveedor_id" name="proveedor_id" class="form-select">
                    <option value="">— Sin proveedor —</option>
                    <?php foreach ($proveedores as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= e($p['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="costo">Costo estimado</label>
                <input type="number" id="costo" name="costo" class="form-control" step="0.01" min="0" value="<?= e((string) old('costo', '0')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="responsable_id">Responsable</label>
                <select id="responsable_id" name="responsable_id" class="form-select">
                    <?php foreach ($responsables as $u): ?>
                    <option value="<?= (int) $u['id'] ?>"><?= e($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción del servicio <span class="required">*</span></label>
            <textarea id="descripcion" name="descripcion" class="form-textarea" required><?= e((string) old('descripcion')) ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Registrar</button>
            <a href="<?= url('mantenimiento') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
