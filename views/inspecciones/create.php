<?php
$pageTitle = 'Nueva inspección';
$vehiculos = $vehiculos ?? [];
$items = $items ?? [];
$lucesTablero = $luces_tablero ?? [];
$preVehiculo = $_GET['vehiculo_id'] ?? old('vehiculo_id');
$selectedLuces = old('luces_tablero', []);
if (!is_array($selectedLuces)) {
    $selectedLuces = [];
}
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('inspecciones') ?>">Inspecciones</a></li><li>/ Nueva</li></ul>
        <h1 class="page-title">Inspección vehicular</h1>
        <p class="page-subtitle">Checklist de 11 ítems — califique cada punto como Bueno, Regular o Malo</p>
    </div>
</div>

<form action="<?= url('inspecciones') ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="card mb-2">
        <div class="card-body">
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
                    <label class="form-label" for="fecha">Fecha <span class="required">*</span></label>
                    <input type="date" id="fecha" name="fecha" class="form-control" required value="<?= e((string) old('fecha', date('Y-m-d'))) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="kilometraje">Kilometraje <span class="required">*</span></label>
                    <input type="number" id="kilometraje" name="kilometraje" class="form-control" required min="0" value="<?= e((string) old('kilometraje')) ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-header"><h3>Checklist (11 ítems)</h3></div>
        <div class="card-body checklist-grid">
            <?php foreach ($items as $item): ?>
            <?php $codigo = $item['codigo']; $sel = old('items.' . $codigo, 'bueno'); ?>
            <div class="checklist-item">
                <div>
                    <div class="checklist-item-name"><?= e($item['nombre']) ?></div>
                    <input type="text" name="obs_items[<?= e($codigo) ?>]" class="form-control mt-1" placeholder="Observaciones (opcional)"
                           value="<?= e((string) old('obs_items.' . $codigo, '')) ?>">
                </div>
                <div class="rating-group">
                    <label class="rating-bueno">
                        <input type="radio" name="items[<?= e($codigo) ?>]" value="bueno" <?= $sel === 'bueno' ? 'checked' : '' ?>>
                        <span>Bueno</span>
                    </label>
                    <label class="rating-regular">
                        <input type="radio" name="items[<?= e($codigo) ?>]" value="regular" <?= $sel === 'regular' ? 'checked' : '' ?>>
                        <span>Regular</span>
                    </label>
                    <label class="rating-malo">
                        <input type="radio" name="items[<?= e($codigo) ?>]" value="malo" <?= $sel === 'malo' ? 'checked' : '' ?>>
                        <span>Malo</span>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-header">
            <h3>Luces del tablero encendidas</h3>
            <p class="card-header-hint">Toque cada ícono para marcar las luces de advertencia que estén encendidas al momento de la inspección.</p>
        </div>
        <div class="card-body">
            <div class="dash-lights-grid" data-dash-lights>
                <?php foreach ($lucesTablero as $luz): ?>
                <?php $codigo = $luz['codigo']; $isOn = in_array($codigo, $selectedLuces, true); ?>
                <label class="dash-light-card<?= $isOn ? ' is-on' : '' ?>">
                    <input type="checkbox" name="luces_tablero[]" value="<?= e($codigo) ?>" <?= $isOn ? 'checked' : '' ?>>
                    <span class="dash-light-icon" aria-hidden="true">
                        <img src="<?= e(asset('images/luces-tablero/' . $luz['icon'])) ?>" alt="" width="48" height="48">
                    </span>
                    <span class="dash-light-name"><?= e($luz['nombre']) ?></span>
                    <span class="dash-light-status"><?= $isOn ? 'Encendida' : 'Apagada' ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="dash-lights-summary mt-2" data-dash-lights-summary>
                <span data-dash-lights-count><?= count($selectedLuces) ?></span> luz(es) seleccionada(s)
            </p>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-body">
            <div class="form-group">
                <label class="form-label" for="observaciones_generales">Observaciones generales</label>
                <textarea id="observaciones_generales" name="observaciones_generales" class="form-textarea"><?= e((string) old('observaciones_generales')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Firma digital del responsable</label>
                <div class="signature-pad-wrapper" data-signature-pad>
                    <canvas></canvas>
                    <div class="signature-actions">
                        <button type="button" class="btn btn-sm btn-secondary" data-signature-clear>Limpiar firma</button>
                    </div>
                    <input type="hidden" name="firma_data" value="">
                </div>
            </div>

            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-primary">Registrar inspección</button>
                <a href="<?= url('formatos/inspeccion') ?>" class="btn btn-secondary" target="_blank">Formato PDF en blanco</a>
                <a href="<?= url('inspecciones') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>
