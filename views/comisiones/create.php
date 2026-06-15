<?php
$pageTitle = 'Nueva comisión';
$vehiculos = $vehiculos ?? [];
$areas = $areas ?? [];
$conductores = $conductores ?? [];
$preVehiculo = $_GET['vehiculo_id'] ?? old('vehiculo_id');
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('comisiones') ?>">Comisiones</a></li><li>/ Nueva</li></ul>
        <h1 class="page-title">Registrar comisión</h1>
    </div>
</div>

<div class="card">
    <form action="<?= url('comisiones') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="vehiculo_id">Vehículo <span class="required">*</span></label>
                <select id="vehiculo_id" name="vehiculo_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($vehiculos as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= (string) $preVehiculo === (string) $v['id'] ? 'selected' : '' ?>>
                        <?= e($v['numero_economico'] . ' — ' . $v['marca'] . ' ' . $v['placas']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="area_solicitante_id">Área solicitante <span class="required">*</span></label>
                <select id="area_solicitante_id" name="area_solicitante_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (string) old('area_solicitante_id') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha <span class="required">*</span></label>
                <input type="date" id="fecha" name="fecha" class="form-control" required value="<?= e((string) old('fecha', date('Y-m-d'))) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="hora_salida">Hora salida <span class="required">*</span></label>
                <input type="time" id="hora_salida" name="hora_salida" class="form-control" required value="<?= e((string) old('hora_salida', '08:00')) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="conductor_nombre">Conductor <span class="required">*</span></label>
                <input type="text" id="conductor_nombre" name="conductor_nombre" class="form-control" required list="conductores-list"
                       value="<?= e((string) old('conductor_nombre')) ?>">
                <datalist id="conductores-list">
                    <?php foreach ($conductores as $u): ?>
                    <option value="<?= e($u['nombre']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label class="form-label" for="conductor_id">Conductor (usuario)</label>
                <select id="conductor_id" name="conductor_id" class="form-select">
                    <option value="">— Opcional —</option>
                    <?php foreach ($conductores as $u): ?>
                    <option value="<?= (int) $u['id'] ?>"><?= e($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="km_salida">Km salida <span class="required">*</span></label>
                <input type="number" id="km_salida" name="km_salida" class="form-control" required min="0" value="<?= e((string) old('km_salida')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="combustible_salida">Combustible salida (%) <span class="required">*</span></label>
                <input type="number" id="combustible_salida" name="combustible_salida" class="form-control" required min="0" max="100" step="0.01"
                       value="<?= e((string) old('combustible_salida', '100')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="destino">Destino <span class="required">*</span></label>
            <input type="text" id="destino" name="destino" class="form-control" required value="<?= e((string) old('destino')) ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="motivo">Motivo <span class="required">*</span></label>
            <textarea id="motivo" name="motivo" class="form-textarea" required><?= e((string) old('motivo')) ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label" for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-textarea"><?= e((string) old('observaciones')) ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar borrador</button>
            <a href="<?= url('comisiones') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
