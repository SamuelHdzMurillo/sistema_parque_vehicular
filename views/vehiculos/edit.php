<?php
$pageTitle = 'Editar vehículo';
$vehiculo = $vehiculo ?? [];
$areas = $areas ?? [];
$responsables = $responsables ?? [];
$tipos_combustible = $tipos_combustible ?? [];
$estados = $estados ?? [];
$fieldErrors = field_errors();
$invalidClass = static fn (string $field): string => isset($fieldErrors[$field]) ? ' is-invalid' : '';
$combustibleLabel = ['gasolina' => 'Gasolina', 'diesel' => 'Diésel', 'hibrido' => 'Híbrido', 'electrico' => 'Eléctrico', 'gnc' => 'GNC'];

$campos = [
    'numero_economico', 'placas', 'serie_vin', 'marca', 'modelo', 'version', 'anio', 'color', 'motor',
    'tipo_combustible', 'capacidad_tanque', 'kilometraje_actual', 'fecha_adquisicion', 'area_id',
    'responsable_id', 'estado', 'observaciones',
];

$v = $vehiculo;
foreach ($campos as $campo) {
    $actual = $vehiculo[$campo] ?? '';
    if ($campo === 'fecha_adquisicion' && is_string($actual) && strlen($actual) >= 10) {
        $actual = substr($actual, 0, 10);
    }
    $v[$campo] = old($campo, (string) $actual);
}

$responsableLabel = static function (array $u): string {
    return trim($u['nombre_completo'] ?? (($u['nombre'] ?? '') . ' ' . ($u['apellido_paterno'] ?? '')));
};
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('vehiculos') ?>">Vehículos</a></li>
            <li><a href="<?= url('vehiculos/' . ($vehiculo['id'] ?? '')) ?>"><?= e($vehiculo['numero_economico'] ?? '') ?></a></li>
            <li>/ Editar</li>
        </ul>
        <h1 class="page-title">Editar vehículo <?= e($vehiculo['numero_economico'] ?? '') ?></h1>
    </div>
</div>

<div class="card">
    <form action="<?= url('vehiculos/' . ($vehiculo['id'] ?? '')) ?>" method="post" class="card-body">
        <?= csrf_field() ?>

        <h3 class="mb-2">Identificación</h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="numero_economico"><?= e(vehiculo_identificador_label()) ?> <span class="required">*</span></label>
                <input type="text" id="numero_economico" name="numero_economico" class="form-control<?= $invalidClass('numero_economico') ?>" required
                       placeholder="<?= e(vehiculo_identificador_placeholder()) ?>"
                       value="<?= e($v['numero_economico']) ?>">
                <?php if (!empty($fieldErrors['numero_economico'])): ?><span class="form-error"><?= e($fieldErrors['numero_economico']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="placas">Placas <span class="required">*</span></label>
                <input type="text" id="placas" name="placas" class="form-control<?= $invalidClass('placas') ?>" required value="<?= e($v['placas']) ?>">
                <?php if (!empty($fieldErrors['placas'])): ?><span class="form-error"><?= e($fieldErrors['placas']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="serie_vin">Serie VIN <span class="required">*</span></label>
                <input type="text" id="serie_vin" name="serie_vin" class="form-control<?= $invalidClass('serie_vin') ?>" required maxlength="17" value="<?= e($v['serie_vin']) ?>">
                <?php if (!empty($fieldErrors['serie_vin'])): ?><span class="form-error"><?= e($fieldErrors['serie_vin']) ?></span><?php endif; ?>
            </div>
        </div>

        <h3 class="mb-2 mt-2">Características</h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="marca">Marca <span class="required">*</span></label>
                <input type="text" id="marca" name="marca" class="form-control<?= $invalidClass('marca') ?>" required value="<?= e($v['marca']) ?>">
                <?php if (!empty($fieldErrors['marca'])): ?><span class="form-error"><?= e($fieldErrors['marca']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="modelo">Modelo <span class="required">*</span></label>
                <input type="text" id="modelo" name="modelo" class="form-control<?= $invalidClass('modelo') ?>" required value="<?= e($v['modelo']) ?>">
                <?php if (!empty($fieldErrors['modelo'])): ?><span class="form-error"><?= e($fieldErrors['modelo']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="version">Versión</label>
                <input type="text" id="version" name="version" class="form-control<?= $invalidClass('version') ?>" value="<?= e($v['version']) ?>">
                <?php if (!empty($fieldErrors['version'])): ?><span class="form-error"><?= e($fieldErrors['version']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="anio">Año <span class="required">*</span></label>
                <input type="number" id="anio" name="anio" class="form-control<?= $invalidClass('anio') ?>" required value="<?= e($v['anio']) ?>">
                <?php if (!empty($fieldErrors['anio'])): ?><span class="form-error"><?= e($fieldErrors['anio']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="color">Color <span class="required">*</span></label>
                <input type="text" id="color" name="color" class="form-control<?= $invalidClass('color') ?>" required value="<?= e($v['color']) ?>">
                <?php if (!empty($fieldErrors['color'])): ?><span class="form-error"><?= e($fieldErrors['color']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="motor">Motor</label>
                <input type="text" id="motor" name="motor" class="form-control<?= $invalidClass('motor') ?>" value="<?= e($v['motor']) ?>">
                <?php if (!empty($fieldErrors['motor'])): ?><span class="form-error"><?= e($fieldErrors['motor']) ?></span><?php endif; ?>
            </div>
        </div>

        <h3 class="mb-2 mt-2">Operación</h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="tipo_combustible">Combustible <span class="required">*</span></label>
                <select id="tipo_combustible" name="tipo_combustible" class="form-select<?= $invalidClass('tipo_combustible') ?>" required>
                    <?php foreach ($tipos_combustible as $tc): ?>
                    <option value="<?= e($tc) ?>" <?= $v['tipo_combustible'] === $tc ? 'selected' : '' ?>><?= e($combustibleLabel[$tc] ?? $tc) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['tipo_combustible'])): ?><span class="form-error"><?= e($fieldErrors['tipo_combustible']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="capacidad_tanque">Capacidad tanque (L) <span class="required">*</span></label>
                <input type="number" id="capacidad_tanque" name="capacidad_tanque" class="form-control<?= $invalidClass('capacidad_tanque') ?>" step="0.01" required value="<?= e($v['capacidad_tanque']) ?>">
                <?php if (!empty($fieldErrors['capacidad_tanque'])): ?><span class="form-error"><?= e($fieldErrors['capacidad_tanque']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="kilometraje_actual">Kilometraje actual</label>
                <input type="number" id="kilometraje_actual" name="kilometraje_actual" class="form-control<?= $invalidClass('kilometraje_actual') ?>" min="0" value="<?= e($v['kilometraje_actual']) ?>">
                <?php if (!empty($fieldErrors['kilometraje_actual'])): ?><span class="form-error"><?= e($fieldErrors['kilometraje_actual']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha_adquisicion">Fecha adquisición <span class="required">*</span></label>
                <input type="date" id="fecha_adquisicion" name="fecha_adquisicion" class="form-control<?= $invalidClass('fecha_adquisicion') ?>" required value="<?= e($v['fecha_adquisicion']) ?>">
                <?php if (!empty($fieldErrors['fecha_adquisicion'])): ?><span class="form-error"><?= e($fieldErrors['fecha_adquisicion']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="area_id">Área <span class="required">*</span></label>
                <select id="area_id" name="area_id" class="form-select<?= $invalidClass('area_id') ?>" required>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (string) $v['area_id'] === (string) $a['id'] ? 'selected' : '' ?>><?= e(catalogo_area_label($a)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['area_id'])): ?><span class="form-error"><?= e($fieldErrors['area_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="responsable_id">Responsable <span class="required">*</span></label>
                <select id="responsable_id" name="responsable_id" class="form-select<?= $invalidClass('responsable_id') ?>" required>
                    <?php foreach ($responsables as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= (string) $v['responsable_id'] === (string) $u['id'] ? 'selected' : '' ?>><?= e($responsableLabel($u)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['responsable_id'])): ?><span class="form-error"><?= e($fieldErrors['responsable_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="estado">Estado</label>
                <select id="estado" name="estado" class="form-select<?= $invalidClass('estado') ?>">
                    <?php
                    $estadosEdit = array_unique(array_merge($estados, [$vehiculo['estado'] ?? '']));
                    foreach ($estadosEdit as $est):
                        if ($est === '') {
                            continue;
                        }
                    ?>
                    <option value="<?= e($est) ?>" <?= $v['estado'] === $est ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $est))) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['estado'])): ?><span class="form-error"><?= e($fieldErrors['estado']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-textarea"><?= e($v['observaciones']) ?></textarea>
        </div>

        <div class="d-flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="<?= url('vehiculos/' . ($vehiculo['id'] ?? '')) ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php if (can('vehiculos.delete') && ($vehiculo['estado'] ?? '') !== 'baja'): ?>
<div class="card mt-2">
    <div class="card-header"><h3>Dar de baja</h3></div>
    <div class="card-body">
        <form action="<?= url('vehiculos/' . $vehiculo['id'] . '/baja') ?>" method="post"
              onsubmit="return confirm('¿Confirma dar de baja este vehículo? Esta acción es irreversible.')">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="motivo">Motivo de baja</label>
                <textarea id="motivo" name="motivo" class="form-textarea" required placeholder="Describa el motivo…"></textarea>
            </div>
            <button type="submit" class="btn btn-danger">Dar de baja vehículo</button>
        </form>
    </div>
</div>
<?php endif; ?>
