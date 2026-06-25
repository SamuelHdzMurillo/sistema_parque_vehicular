<?php
$pageTitle = 'Nueva comisión';
$vehiculos = $vehiculos ?? [];
$areas = $areas ?? [];
$planteles = $planteles ?? [];
$conductores = $conductores ?? [];
$usuarios = $usuarios ?? [];
$folioSugerido = (string) old('folio', $folio_sugerido ?? '');
$preVehiculo = $_GET['vehiculo_id'] ?? old('vehiculo_id');
$respRegresoSeleccionado = 0;
$nombreRegreso = trim((string) old('responsable_regreso_nombre', ''));
if ($nombreRegreso !== '') {
    foreach ($conductores as $cond) {
        if ($cond['nombre'] === $nombreRegreso) {
            $respRegresoSeleccionado = (int) $cond['id'];
            break;
        }
    }
}
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
                <label class="form-label" for="folio">Folio <span class="required">*</span></label>
                <input type="text" id="folio" name="folio" class="form-control" required
                       pattern="COM-\d{4}-\d+"
                       title="Formato: COM-AAAA-NNNN (ejemplo: COM-2026-0001)"
                       value="<?= e($folioSugerido) ?>">
                <small class="form-hint text-muted">Se propone el siguiente consecutivo; puede modificarlo antes de guardar.</small>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="vehiculo_id">Vehículo <span class="required">*</span></label>
                <select id="vehiculo_id" name="vehiculo_id" class="form-select" required data-km-source data-luces-autofill>
                    <option value="">Seleccione…</option>
                    <?php foreach ($vehiculos as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" data-km="<?= (int) ($v['kilometraje_actual'] ?? 0) ?>" <?= (string) $preVehiculo === (string) $v['id'] ? 'selected' : '' ?>>
                        <?= e(catalogo_vehiculo_label($v)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="area_solicitante_id">Área solicitante <span class="required">*</span></label>
                <div class="input-group" data-area-select-group>
                    <select id="area_solicitante_id" name="area_solicitante_id" class="form-select" required data-area-select>
                        <option value="">Seleccione…</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= (int) $a['id'] ?>" <?= (string) old('area_solicitante_id') === (string) $a['id'] ? 'selected' : '' ?>><?= e(catalogo_area_label($a)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('catalogos.create')): ?>
                    <button type="button" class="btn btn-accent" data-area-quick-open title="Agregar área" aria-label="Agregar área">+</button>
                    <?php endif; ?>
                </div>
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
                <label class="form-label" for="conductor_id">Conductor <span class="required">*</span></label>
                <div class="input-group">
                    <select id="conductor_id" name="conductor_id" class="form-select" required data-conductor-select>
                        <option value="">Seleccione…</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"
                                data-nombre="<?= e($c['nombre']) ?>"
                                data-telefono="<?= e($c['telefono']) ?>"
                                <?= (string) old('conductor_id') === (string) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nombre']) ?> — <?= e($c['area_label'] ?? catalogo_area_label($c)) ?> — <?= e($c['telefono']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('catalogos.create')): ?>
                    <button type="button" class="btn btn-accent" data-conductor-quick-open data-target-select="conductor_id" title="Agregar conductor" aria-label="Agregar conductor">+</button>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="conductor_nombre" name="conductor_nombre" value="<?= e((string) old('conductor_nombre')) ?>">
                <small class="form-hint text-muted" data-conductor-telefono></small>
            </div>
            <div class="form-group">
                <label class="form-label" for="km_salida">Km salida <span class="required">*</span></label>
                <input type="number" id="km_salida" name="km_salida" class="form-control" required min="0"
                       data-km-target value="<?= e((string) old('km_salida')) ?>">
                <small class="form-hint text-muted" data-km-hint>Seleccione un vehículo para ver el kilometraje actual.</small>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="responsable_regreso_conductor">Responsable de regreso (quien trae el vehículo)</label>
                <div class="input-group">
                    <select id="responsable_regreso_conductor" class="form-select" data-responsable-regreso-select>
                        <option value="">— Opcional —</option>
                        <?php foreach ($conductores as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"
                                data-nombre="<?= e($c['nombre']) ?>"
                                data-telefono="<?= e($c['telefono']) ?>"
                                <?= $respRegresoSeleccionado === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nombre']) ?> — <?= e($c['area_label'] ?? catalogo_area_label($c)) ?> — <?= e($c['telefono']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('catalogos.create')): ?>
                    <button type="button" class="btn btn-accent" data-conductor-quick-open data-target-select="responsable_regreso_conductor" title="Agregar conductor" aria-label="Agregar conductor">+</button>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="responsable_regreso_nombre" name="responsable_regreso_nombre" value="<?= e((string) old('responsable_regreso_nombre')) ?>">
                <input type="hidden" name="responsable_regreso_id" value="">
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

        <?php
        $lucesTablero = $luces_tablero ?? [];
        $lucesSalida = old('luces_salida', []);
        if (!is_array($lucesSalida)) {
            $lucesSalida = [];
        }
        if ($lucesSalida === [] && !empty($vehiculo_luces_preset) && is_array($vehiculo_luces_preset)) {
            $lucesSalida = $vehiculo_luces_preset;
        }
        ?>
        <div class="form-group">
            <label class="form-label">Luces del tablero encendidas (a la salida)</label>
            <p class="card-header-hint">Marque las luces de advertencia encendidas al momento de la salida. Se cargan automáticamente del último registro del vehículo (inspección o comisión anterior).</p>
            <div class="dash-lights-grid" data-dash-lights>
                <?php foreach ($lucesTablero as $luz): ?>
                <?php $codigo = $luz['codigo']; $isOn = in_array($codigo, $lucesSalida, true); ?>
                <label class="dash-light-card<?= $isOn ? ' is-on' : '' ?>">
                    <input type="checkbox" name="luces_salida[]" value="<?= e($codigo) ?>" <?= $isOn ? 'checked' : '' ?>>
                    <span class="dash-light-icon" aria-hidden="true">
                        <img src="<?= e(asset('images/luces-tablero/' . $luz['icon'])) ?>" alt="" width="48" height="48">
                    </span>
                    <span class="dash-light-name"><?= e($luz['nombre']) ?></span>
                    <span class="dash-light-status"><?= $isOn ? 'Encendida' : 'Apagada' ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="dash-lights-summary mt-2" data-dash-lights-summary>
                <span data-dash-lights-count><?= count($lucesSalida) ?></span> luz(es) seleccionada(s)
            </p>
        </div>

        <?php
        $liquidos = $liquidos ?? [];
        $nivelOpciones = $nivel_opciones ?? [];
        $nivelesSalida = old('niveles_salida', []);
        if (!is_array($nivelesSalida)) {
            $nivelesSalida = [];
        }
        ?>
        <div class="form-group">
            <label class="form-label">Niveles de líquidos (a la salida)</label>
            <div class="checklist-grid">
                <?php foreach ($liquidos as $liq): ?>
                <?php $cod = $liq['codigo']; $sel = (string) ($nivelesSalida[$cod] ?? 'lleno'); ?>
                <div class="checklist-item">
                    <div class="checklist-item-name"><?= e($liq['nombre']) ?></div>
                    <div class="rating-group">
                        <label class="rating-bueno">
                            <input type="radio" name="niveles_salida[<?= e($cod) ?>]" value="lleno" <?= $sel === 'lleno' ? 'checked' : '' ?>>
                            <span>Lleno</span>
                        </label>
                        <label class="rating-regular">
                            <input type="radio" name="niveles_salida[<?= e($cod) ?>]" value="medio" <?= $sel === 'medio' ? 'checked' : '' ?>>
                            <span>Medio</span>
                        </label>
                        <label class="rating-malo">
                            <input type="radio" name="niveles_salida[<?= e($cod) ?>]" value="bajo" <?= $sel === 'bajo' ? 'checked' : '' ?>>
                            <span>Bajo</span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        $herramientasCatalogo = $herramientas_catalogo ?? [];
        $herramientasSalida = old('herramientas_salida', []);
        if (!is_array($herramientasSalida)) {
            $herramientasSalida = [];
        }
        if ($herramientasSalida === [] && !empty($vehiculo_herramientas_preset) && is_array($vehiculo_herramientas_preset)) {
            $herramientasSalida = $vehiculo_herramientas_preset;
        }
        App\Core\View::component('herramientas-checklist', [
            'name' => 'herramientas_salida[]',
            'label' => 'Herramientas entregadas en salida',
            'catalogo' => $herramientasCatalogo,
            'selected' => $herramientasSalida,
            'hint' => 'Marque las herramientas que se entregan con el vehículo. Se preseleccionan las que están presentes en el inventario.',
        ]);
        ?>
        <?php App\Core\View::component('combustible-fraccion-select', [
            'id' => 'combustible_salida',
            'name' => 'combustible_salida',
            'label' => 'Combustible salida',
            'valuePorcentaje' => old_nonempty('combustible_salida', 100),
            'required' => true,
        ]); ?>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar borrador</button>
            <a href="<?= url('formatos/comision') ?>" class="btn btn-secondary" target="_blank">Formato PDF en blanco</a>
            <a href="<?= url('comisiones') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php if (can('catalogos.create')): ?>
<?php App\Core\View::component('modal-area-quick', ['planteles' => $planteles]); ?>
<?php App\Core\View::component('modal-plantel-quick'); ?>
<?php App\Core\View::component('modal-conductor-quick', ['areas' => $areas]); ?>
<?php endif; ?>
