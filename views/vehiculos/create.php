<?php
$pageTitle = 'Registrar vehículo';
$areas = $areas ?? [];
$responsables = $responsables ?? [];
$tipos_combustible = $tipos_combustible ?? [];
$estados = $estados ?? [];
$fieldErrors = field_errors();
$invalidClass = static fn (string $field): string => isset($fieldErrors[$field]) ? ' is-invalid' : '';
$combustibleLabel = ['gasolina' => 'Gasolina', 'diesel' => 'Diésel', 'hibrido' => 'Híbrido', 'electrico' => 'Eléctrico', 'gnc' => 'GNC'];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('vehiculos') ?>">Vehículos</a></li>
            <li>/ Nuevo</li>
        </ul>
        <h1 class="page-title">Registrar vehículo</h1>
    </div>
</div>

<div class="card">
    <form action="<?= url('vehiculos') ?>" method="post" enctype="multipart/form-data" class="card-body">
        <?= csrf_field() ?>

        <h3 class="mb-2">Identificación</h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="numero_economico"><?= e(vehiculo_identificador_label()) ?> <span class="required">*</span></label>
                <input type="text" id="numero_economico" name="numero_economico" class="form-control<?= $invalidClass('numero_economico') ?>" required
                       placeholder="<?= e(vehiculo_identificador_placeholder()) ?>"
                       value="<?= e((string) old('numero_economico')) ?>">
                <?php if (!empty($fieldErrors['numero_economico'])): ?><span class="form-error"><?= e($fieldErrors['numero_economico']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="placas">Placas <span class="required">*</span></label>
                <input type="text" id="placas" name="placas" class="form-control<?= $invalidClass('placas') ?>" required
                       value="<?= e((string) old('placas')) ?>">
                <?php if (!empty($fieldErrors['placas'])): ?><span class="form-error"><?= e($fieldErrors['placas']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="serie_vin">Serie VIN <span class="required">*</span></label>
                <input type="text" id="serie_vin" name="serie_vin" class="form-control<?= $invalidClass('serie_vin') ?>" required maxlength="17"
                       value="<?= e((string) old('serie_vin')) ?>">
                <?php if (!empty($fieldErrors['serie_vin'])): ?><span class="form-error"><?= e($fieldErrors['serie_vin']) ?></span><?php endif; ?>
            </div>
        </div>

        <h3 class="mb-2 mt-2">Características</h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="marca">Marca <span class="required">*</span></label>
                <input type="text" id="marca" name="marca" class="form-control<?= $invalidClass('marca') ?>" required value="<?= e((string) old('marca')) ?>">
                <?php if (!empty($fieldErrors['marca'])): ?><span class="form-error"><?= e($fieldErrors['marca']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="modelo">Modelo <span class="required">*</span></label>
                <input type="text" id="modelo" name="modelo" class="form-control<?= $invalidClass('modelo') ?>" required value="<?= e((string) old('modelo')) ?>">
                <?php if (!empty($fieldErrors['modelo'])): ?><span class="form-error"><?= e($fieldErrors['modelo']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="version">Versión</label>
                <input type="text" id="version" name="version" class="form-control<?= $invalidClass('version') ?>" value="<?= e((string) old('version')) ?>">
                <?php if (!empty($fieldErrors['version'])): ?><span class="form-error"><?= e($fieldErrors['version']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="anio">Año <span class="required">*</span></label>
                <input type="number" id="anio" name="anio" class="form-control<?= $invalidClass('anio') ?>" required min="1990" max="2030"
                       value="<?= e((string) old('anio', date('Y'))) ?>">
                <?php if (!empty($fieldErrors['anio'])): ?><span class="form-error"><?= e($fieldErrors['anio']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="color">Color <span class="required">*</span></label>
                <input type="text" id="color" name="color" class="form-control<?= $invalidClass('color') ?>" required value="<?= e((string) old('color')) ?>">
                <?php if (!empty($fieldErrors['color'])): ?><span class="form-error"><?= e($fieldErrors['color']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="motor">Motor</label>
                <input type="text" id="motor" name="motor" class="form-control<?= $invalidClass('motor') ?>" value="<?= e((string) old('motor')) ?>">
                <?php if (!empty($fieldErrors['motor'])): ?><span class="form-error"><?= e($fieldErrors['motor']) ?></span><?php endif; ?>
            </div>
        </div>

        <h3 class="mb-2 mt-2">Operación</h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="tipo_combustible">Tipo combustible <span class="required">*</span></label>
                <select id="tipo_combustible" name="tipo_combustible" class="form-select<?= $invalidClass('tipo_combustible') ?>" required>
                    <?php foreach ($tipos_combustible as $tc): ?>
                    <option value="<?= e($tc) ?>" <?= old('tipo_combustible') === $tc ? 'selected' : '' ?>><?= e($combustibleLabel[$tc] ?? $tc) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['tipo_combustible'])): ?><span class="form-error"><?= e($fieldErrors['tipo_combustible']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="capacidad_tanque">Capacidad tanque (L) <span class="required">*</span></label>
                <input type="number" id="capacidad_tanque" name="capacidad_tanque" class="form-control<?= $invalidClass('capacidad_tanque') ?>" required step="0.01" min="0"
                       value="<?= e((string) old('capacidad_tanque', '50')) ?>">
                <?php if (!empty($fieldErrors['capacidad_tanque'])): ?><span class="form-error"><?= e($fieldErrors['capacidad_tanque']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="kilometraje_actual">Kilometraje actual</label>
                <input type="number" id="kilometraje_actual" name="kilometraje_actual" class="form-control<?= $invalidClass('kilometraje_actual') ?>" min="0"
                       value="<?= e((string) old('kilometraje_actual', '0')) ?>">
                <?php if (!empty($fieldErrors['kilometraje_actual'])): ?><span class="form-error"><?= e($fieldErrors['kilometraje_actual']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha_adquisicion">Fecha adquisición <span class="required">*</span></label>
                <input type="date" id="fecha_adquisicion" name="fecha_adquisicion" class="form-control<?= $invalidClass('fecha_adquisicion') ?>" required
                       value="<?= e((string) old('fecha_adquisicion')) ?>">
                <?php if (!empty($fieldErrors['fecha_adquisicion'])): ?><span class="form-error"><?= e($fieldErrors['fecha_adquisicion']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="area_id">Área <span class="required">*</span></label>
                <select id="area_id" name="area_id" class="form-select<?= $invalidClass('area_id') ?>" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($areas as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (string) old('area_id') === (string) $a['id'] ? 'selected' : '' ?>><?= e(catalogo_area_label($a)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['area_id'])): ?><span class="form-error"><?= e($fieldErrors['area_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="responsable_id">Responsable <span class="required">*</span></label>
                <select id="responsable_id" name="responsable_id" class="form-select<?= $invalidClass('responsable_id') ?>" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($responsables as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= (string) old('responsable_id') === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['responsable_id'])): ?><span class="form-error"><?= e($fieldErrors['responsable_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="estado">Estado inicial</label>
                <select id="estado" name="estado" class="form-select<?= $invalidClass('estado') ?>">
                    <?php foreach ($estados as $est): ?>
                    <option value="<?= e($est) ?>" <?= old('estado', 'disponible') === $est ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $est))) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['estado'])): ?><span class="form-error"><?= e($fieldErrors['estado']) ?></span><?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-textarea"><?= e((string) old('observaciones')) ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="fotos">Fotografías del vehículo</label>
            <input type="file" id="fotos" name="fotos[]" class="form-control<?= $invalidClass('fotos') ?>" accept="image/jpeg,image/png,image/webp" multiple>
            <?php if (!empty($fieldErrors['fotos'])): ?><span class="form-error"><?= e($fieldErrors['fotos']) ?></span><?php endif; ?>
            <p class="form-hint">Seleccione todas las imágenes de una vez. La primera será la principal; en el expediente podrá borrar las incorrectas o cambiar la principal.</p>
        </div>

        <div class="d-flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Registrar vehículo</button>
            <a href="<?= url('vehiculos') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
