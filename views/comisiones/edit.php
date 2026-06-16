<?php
$pageTitle = 'Editar comisión';
$comision = $comision ?? [];
$vehiculos = $vehiculos ?? [];
$areas = $areas ?? [];
$conductores = $conductores ?? [];
$c = array_merge($comision, array_intersect_key($_SESSION['_old'] ?? [], array_flip(array_keys($comision))));
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('comisiones') ?>">Comisiones</a></li>
            <li><a href="<?= url('comisiones/' . $c['id']) ?>"><?= e($c['folio']) ?></a></li>
            <li>/ Editar</li>
        </ul>
        <h1 class="page-title">Editar comisión <?= e($c['folio']) ?></h1>
    </div>
</div>

<div class="card">
    <form action="<?= url('comisiones/' . $c['id']) ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="vehiculo_id">Vehículo</label>
                <select id="vehiculo_id" name="vehiculo_id" class="form-select" required>
                    <?php foreach ($vehiculos as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= (int) $c['vehiculo_id'] === (int) $v['id'] ? 'selected' : '' ?>>
                        <?= e($v['numero_economico'] . ' — ' . ($v['placas'] ?? '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="area_solicitante_id">Área solicitante</label>
                <select id="area_solicitante_id" name="area_solicitante_id" class="form-select" required>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (int) $c['area_solicitante_id'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha</label>
                <input type="date" id="fecha" name="fecha" class="form-control" value="<?= e($c['fecha']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="hora_salida">Hora salida</label>
                <input type="time" id="hora_salida" name="hora_salida" class="form-control" value="<?= e(substr($c['hora_salida'] ?? '', 0, 5)) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="conductor_nombre">Conductor</label>
                <input type="text" id="conductor_nombre" name="conductor_nombre" class="form-control" value="<?= e($c['conductor_nombre']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="km_salida">Km salida</label>
                <input type="number" id="km_salida" name="km_salida" class="form-control" value="<?= e((string) $c['km_salida']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="combustible_salida">Combustible salida (%)</label>
                <input type="number" id="combustible_salida" name="combustible_salida" class="form-control" step="0.01" value="<?= e((string) $c['combustible_salida']) ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="responsable_regreso_nombre">Responsable de regreso (quien trae el vehículo)</label>
                <input type="text" id="responsable_regreso_nombre" name="responsable_regreso_nombre" class="form-control" list="responsables-regreso-list"
                       placeholder="Seleccione o escriba el nombre" value="<?= e((string) ($c['responsable_regreso_nombre'] ?? '')) ?>">
                <datalist id="responsables-regreso-list">
                    <?php foreach ($conductores as $u): ?>
                    <option value="<?= e($u['nombre_completo'] ?? $u['nombre']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label class="form-label" for="responsable_regreso_id">Responsable de regreso (usuario)</label>
                <select id="responsable_regreso_id" name="responsable_regreso_id" class="form-select">
                    <option value="">— Opcional —</option>
                    <?php foreach ($conductores as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= (int) ($c['responsable_regreso_id'] ?? 0) === (int) $u['id'] ? 'selected' : '' ?>>
                        <?= e($u['nombre_completo'] ?? $u['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="destino">Destino</label>
            <input type="text" id="destino" name="destino" class="form-control" value="<?= e($c['destino']) ?>" required>
        </div>
        <div class="form-group">
            <label class="form-label" for="motivo">Motivo</label>
            <textarea id="motivo" name="motivo" class="form-textarea" required><?= e($c['motivo']) ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label" for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-textarea"><?= e($c['observaciones'] ?? '') ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('comisiones/' . $c['id']) ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
