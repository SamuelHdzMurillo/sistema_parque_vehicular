<?php
$pageTitle = 'Editar comisión';
$comision = $comision ?? [];
$vehiculos = $vehiculos ?? [];
$areas = $areas ?? [];
$planteles = $planteles ?? [];
$conductores = $conductores ?? [];
$usuarios = $usuarios ?? [];
$c = array_merge($comision, array_intersect_key($_SESSION['_old'] ?? [], array_flip(array_keys($comision))));
$respRegresoSeleccionado = 0;
$nombreRegreso = trim((string) ($c['responsable_regreso_nombre'] ?? ''));
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
                        <?= e(catalogo_vehiculo_label($v)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="area_solicitante_id">Área solicitante</label>
                <div class="input-group" data-area-select-group>
                    <select id="area_solicitante_id" name="area_solicitante_id" class="form-select" required data-area-select>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= (int) $a['id'] ?>" <?= (int) $c['area_solicitante_id'] === (int) $a['id'] ? 'selected' : '' ?>><?= e(catalogo_area_label($a)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('catalogos.create')): ?>
                    <button type="button" class="btn btn-accent" data-area-quick-open title="Agregar área" aria-label="Agregar área">+</button>
                    <?php endif; ?>
                </div>
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
                <label class="form-label" for="conductor_id">Conductor</label>
                <div class="input-group">
                    <select id="conductor_id" name="conductor_id" class="form-select" required data-conductor-select>
                        <option value="">Seleccione…</option>
                        <?php foreach ($conductores as $cond): ?>
                        <option value="<?= (int) $cond['id'] ?>"
                                data-nombre="<?= e($cond['nombre']) ?>"
                                data-telefono="<?= e($cond['telefono']) ?>"
                                <?= (int) ($c['conductor_id'] ?? 0) === (int) $cond['id'] ? 'selected' : '' ?>>
                            <?= e($cond['nombre']) ?> — <?= e($cond['area_label'] ?? catalogo_area_label($cond)) ?> — <?= e($cond['telefono']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('catalogos.create')): ?>
                    <button type="button" class="btn btn-accent" data-conductor-quick-open data-target-select="conductor_id" title="Agregar conductor" aria-label="Agregar conductor">+</button>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="conductor_nombre" name="conductor_nombre" value="<?= e($c['conductor_nombre']) ?>">
                <small class="form-hint text-muted" data-conductor-telefono></small>
            </div>
            <div class="form-group">
                <label class="form-label" for="km_salida">Km salida</label>
                <input type="number" id="km_salida" name="km_salida" class="form-control" value="<?= e((string) $c['km_salida']) ?>" required>
            </div>
            <div class="form-group">
                <?php App\Core\View::component('combustible-fraccion-select', [
                    'id' => 'combustible_salida',
                    'name' => 'combustible_salida',
                    'label' => 'Combustible salida',
                    'valuePorcentaje' => $c['combustible_salida'] ?? null,
                    'required' => true,
                ]); ?>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="responsable_regreso_conductor">Responsable de regreso (quien trae el vehículo)</label>
                <div class="input-group">
                    <select id="responsable_regreso_conductor" class="form-select" data-responsable-regreso-select>
                        <option value="">— Opcional —</option>
                        <?php foreach ($conductores as $cond): ?>
                        <option value="<?= (int) $cond['id'] ?>"
                                data-nombre="<?= e($cond['nombre']) ?>"
                                data-telefono="<?= e($cond['telefono']) ?>"
                                <?= $respRegresoSeleccionado === (int) $cond['id'] ? 'selected' : '' ?>>
                            <?= e($cond['nombre']) ?> — <?= e($cond['area_label'] ?? catalogo_area_label($cond)) ?> — <?= e($cond['telefono']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('catalogos.create')): ?>
                    <button type="button" class="btn btn-accent" data-conductor-quick-open data-target-select="responsable_regreso_conductor" title="Agregar conductor" aria-label="Agregar conductor">+</button>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="responsable_regreso_nombre" name="responsable_regreso_nombre" value="<?= e((string) ($c['responsable_regreso_nombre'] ?? '')) ?>">
                <input type="hidden" name="responsable_regreso_id" value="">
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

        <?php
        $lucesTablero = $luces_tablero ?? [];
        $lucesSalida = $c['luces_salida'] ?? [];
        if (!is_array($lucesSalida)) {
            $lucesSalida = [];
        }
        ?>
        <div class="form-group">
            <label class="form-label">Luces del tablero encendidas (a la salida)</label>
            <p class="card-header-hint">Marque las luces de advertencia encendidas al momento de la salida.</p>
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
        $nivelesSalida = $c['niveles_salida'] ?? [];
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

        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('comisiones/' . $c['id']) ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php if (can('catalogos.create')): ?>
<?php App\Core\View::component('modal-area-quick', ['planteles' => $planteles]); ?>
<?php App\Core\View::component('modal-plantel-quick'); ?>
<?php App\Core\View::component('modal-conductor-quick', ['areas' => $areas]); ?>
<?php endif; ?>
