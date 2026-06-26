<?php
$pageTitle = 'Editar inspección';
$inspeccion = $inspeccion ?? [];
$i = $inspeccion;
$vehiculos = $vehiculos ?? [];
$items = $items ?? [];
$lucesTablero = $luces_tablero ?? [];
$itemValues = [];
$itemObs = [];
foreach ($i['items'] ?? [] as $row) {
    $itemValues[$row['item_codigo']] = $row['calificacion'];
    $itemObs[$row['item_codigo']] = $row['observaciones'] ?? '';
}
$selectedLuces = old('luces_tablero', array_column($i['luces_tablero'] ?? [], 'luz_codigo'));
if (!is_array($selectedLuces)) {
    $selectedLuces = [];
}
$preVehiculo = old('vehiculo_id', $i['vehiculo_id'] ?? '');
$esHistorico = old('es_historico', !empty($i['es_historico']));
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('inspecciones') ?>">Inspecciones</a></li>
            <li><a href="<?= url('inspecciones/' . $i['id']) ?>"><?= e(inspeccion_folio($i)) ?></a></li>
            <li>/ Editar</li>
        </ul>
        <h1 class="page-title">Editar inspección — <?= e(inspeccion_folio($i)) ?></h1>
        <p class="page-subtitle">Checklist de 11 ítems — califique cada punto como Bueno, Regular o Malo</p>
    </div>
</div>

<form action="<?= url('inspecciones/' . $i['id']) ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="card mb-2">
        <div class="card-body">
            <?php App\Core\View::component('folio-input', [
                'id' => 'folio',
                'tipo' => 'INS',
                'pad' => 4,
                'sugerido' => (string) ($i['folio'] ?? ''),
                'label' => 'Folio de inspección',
                'required' => true,
                'hint' => 'Formato INS-AAAA-NNNN. Debe ser único en el sistema.',
            ]); ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="vehiculo_id">Vehículo <span class="required">*</span></label>
                    <select id="vehiculo_id" name="vehiculo_id" class="form-select" required data-luces-autofill data-km-source>
                        <option value="">Seleccione…</option>
                        <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= (int) $v['id'] ?>" data-km="<?= (int) ($v['kilometraje_actual'] ?? 0) ?>" <?= (string) $preVehiculo === (string) $v['id'] ? 'selected' : '' ?>>
                            <?= e(catalogo_vehiculo_label($v)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="fecha">Fecha <span class="required">*</span></label>
                    <input type="date" id="fecha" name="fecha" class="form-control" required value="<?= e((string) old('fecha', $i['fecha'] ?? date('Y-m-d'))) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="kilometraje">Kilometraje <span class="required">*</span></label>
                    <input type="number" id="kilometraje" name="kilometraje" class="form-control" required min="0" data-km-target value="<?= e((string) old('kilometraje', $i['kilometraje'] ?? '')) ?>">
                    <small class="form-hint text-muted" data-km-hint>Seleccione un vehículo para ver el kilometraje actual.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">&nbsp;</label>
                    <label class="form-check">
                        <input type="checkbox" name="es_historico" value="1" data-km-historic-toggle <?= $esHistorico ? 'checked' : '' ?>>
                        Inspección olvidada (fecha o kilometraje anterior)
                    </label>
                    <small class="form-hint text-muted">Marque esta opción si la inspección se realizó antes y no actualizó el odómetro del vehículo.</small>
                </div>
            </div>
        <?php App\Core\View::component('combustible-fraccion-select', [
            'id' => 'nivel_combustible',
            'name' => 'nivel_combustible',
            'label' => 'Nivel de combustible (gasolina)',
            'valuePorcentaje' => old_nonempty('nivel_combustible', $i['nivel_combustible'] ?? null),
            'required' => false,
        ]); ?>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-header"><h3>Checklist (11 ítems)</h3></div>
        <div class="card-body checklist-grid">
            <?php foreach ($items as $item): ?>
            <?php
            $codigo = $item['codigo'];
            $sel = old('items.' . $codigo, $itemValues[$codigo] ?? 'bueno');
            ?>
            <div class="checklist-item">
                <div>
                    <div class="checklist-item-name"><?= e($item['nombre']) ?></div>
                    <input type="text" name="obs_items[<?= e($codigo) ?>]" class="form-control mt-1" placeholder="Observaciones (opcional)"
                           value="<?= e((string) old('obs_items.' . $codigo, $itemObs[$codigo] ?? '')) ?>">
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
                <textarea id="observaciones_generales" name="observaciones_generales" class="form-textarea"><?= e((string) old('observaciones_generales', $i['observaciones_generales'] ?? '')) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Firma digital del responsable</label>
                <?php if (!empty($i['firma_digital'])): ?>
                <p class="form-hint text-muted mb-1">Firma actual registrada. Dibuje abajo solo si desea reemplazarla.</p>
                <img src="<?= e(url('storage/uploads/' . ltrim($i['firma_digital'], '/'))) ?>" alt="Firma actual" style="max-width:240px;border:1px solid var(--border-color);border-radius:8px;margin-bottom:0.75rem">
                <?php endif; ?>
                <div class="signature-pad-wrapper" data-signature-pad>
                    <canvas></canvas>
                    <div class="signature-actions">
                        <button type="button" class="btn btn-sm btn-secondary" data-signature-clear>Limpiar firma</button>
                    </div>
                    <input type="hidden" name="firma_data" value="">
                </div>
            </div>

            <div class="d-flex gap-1">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="<?= url('inspecciones/' . $i['id']) ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>
    </div>
</form>
