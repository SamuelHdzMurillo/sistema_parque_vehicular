<?php
$pageTitle = 'Configuración de alertas';
$config = $config ?? [];
$vehiculos = $vehiculos ?? [];
$vehiculo_id = $vehiculo_id ?? null;
$vehiculo_config = $vehiculo_config ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('alertas') ?>">Alertas</a></li><li>/ Configuración</li></ul>
        <h1 class="page-title">Configuración de alertas</h1>
        <p class="page-subtitle">Umbrales globales y personalización por vehículo (kilometraje o tiempo)</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Configuración por vehículo</strong></div>
    <div class="card-body">
        <form method="get" action="<?= url('alertas/config') ?>" class="form-row mb-3">
            <div class="form-group" style="flex:1;max-width:480px">
                <label class="form-label" for="vehiculo_id">Seleccionar vehículo</label>
                <select id="vehiculo_id" name="vehiculo_id" class="form-select" onchange="this.form.submit()">
                    <option value="">— Valores por defecto (todos los vehículos) —</option>
                    <?php foreach ($vehiculos as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= (int) $vehiculo_id === (int) $v['id'] ? 'selected' : '' ?>>
                        <?= e(catalogo_vehiculo_label($v)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if (!empty($vehiculo_id) && !empty($vehiculo_config)): ?>
        <form action="<?= url('alertas/config') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="vehiculo_id" value="<?= (int) $vehiculo_id ?>">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Personalizar</th>
                            <th>Tipo</th>
                            <th>Nombre</th>
                            <th>Umbral verde</th>
                            <th>Umbral amarillo</th>
                            <th>Umbral rojo</th>
                            <?php $hayKm = false; foreach ($vehiculo_config as $vc) { if (($vc['unidad'] ?? '') === 'km') { $hayKm = true; break; } } ?>
                            <?php if ($hayKm): ?>
                            <th>Verde (días)</th>
                            <th>Amarillo (días)</th>
                            <th>Rojo (días)</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehiculo_config as $row): ?>
                        <?php $tipo = (string) ($row['tipo'] ?? ''); $esKm = ($row['unidad'] ?? '') === 'km'; ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="vehiculo_config[<?= e($tipo) ?>][personalizado]" value="1"
                                       <?= !empty($row['personalizado']) ? 'checked' : '' ?>>
                            </td>
                            <td><?= e($tipo) ?></td>
                            <td><?= e($row['nombre'] ?? '') ?></td>
                            <td>
                                <input type="number" name="vehiculo_config[<?= e($tipo) ?>][umbral_verde]" class="form-control" min="0"
                                       value="<?= e((string) ($row['umbral_verde'] ?? '')) ?>">
                            </td>
                            <td>
                                <input type="number" name="vehiculo_config[<?= e($tipo) ?>][umbral_amarillo]" class="form-control" min="0"
                                       value="<?= e((string) ($row['umbral_amarillo'] ?? '')) ?>">
                            </td>
                            <td>
                                <input type="number" name="vehiculo_config[<?= e($tipo) ?>][umbral_rojo]" class="form-control" min="0"
                                       value="<?= e((string) ($row['umbral_rojo'] ?? '')) ?>">
                            </td>
                            <?php if ($hayKm): ?>
                            <td>
                                <?php if ($esKm): ?>
                                <input type="number" name="vehiculo_config[<?= e($tipo) ?>][umbral_verde_dias]" class="form-control" min="0"
                                       value="<?= e((string) ($row['umbral_verde_dias'] ?? '')) ?>">
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($esKm): ?>
                                <input type="number" name="vehiculo_config[<?= e($tipo) ?>][umbral_amarillo_dias]" class="form-control" min="0"
                                       value="<?= e((string) ($row['umbral_amarillo_dias'] ?? '')) ?>">
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($esKm): ?>
                                <input type="number" name="vehiculo_config[<?= e($tipo) ?>][umbral_rojo_dias]" class="form-control" min="0"
                                       value="<?= e((string) ($row['umbral_rojo_dias'] ?? '')) ?>">
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="form-hint">
                Marque <strong>Personalizar</strong> en los tipos de alerta que desee configurar distinto para este vehículo.
                Si no marca la casilla, se usarán los valores por defecto globales.
            </p>
            <button type="submit" class="btn btn-primary">Guardar configuración del vehículo</button>
        </form>
        <?php elseif (!empty($vehiculo_id)): ?>
        <p class="text-muted">No hay tipos de alerta configurados.</p>
        <?php else: ?>
        <p class="text-muted">Elija un vehículo arriba para definir umbrales personalizados, o use los valores por defecto de abajo.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Valores por defecto (todos los vehículos)</strong></div>
    <form action="<?= url('alertas/config') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Unidad</th>
                        <th>Umbral verde (km/días)</th>
                        <th>Umbral amarillo</th>
                        <th>Umbral rojo</th>
                        <th>Umbral verde (días)*</th>
                        <th>Umbral amarillo (días)</th>
                        <th>Umbral rojo (días)</th>
                        <th>Activo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($config)): ?>
                    <tr><td colspan="10" class="text-center text-muted">No hay configuración cargada</td></tr>
                    <?php else: foreach ($config as $row): ?>
                    <?php $id = (int) ($row['id'] ?? 0); $esKm = ($row['unidad'] ?? '') === 'km'; ?>
                    <tr>
                        <td><?= e($row['tipo'] ?? '') ?></td>
                        <td>
                            <input type="text" name="config[<?= $id ?>][nombre]" class="form-control" value="<?= e($row['nombre'] ?? '') ?>">
                        </td>
                        <td><?= e($row['unidad'] ?? 'km') ?></td>
                        <td>
                            <input type="number" name="config[<?= $id ?>][umbral_verde]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_verde'] ?? '')) ?>">
                        </td>
                        <td>
                            <input type="number" name="config[<?= $id ?>][umbral_amarillo]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_amarillo'] ?? '')) ?>">
                        </td>
                        <td>
                            <input type="number" name="config[<?= $id ?>][umbral_rojo]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_rojo'] ?? '')) ?>">
                        </td>
                        <td>
                            <?php if ($esKm): ?>
                            <input type="number" name="config[<?= $id ?>][umbral_verde_dias]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_verde_dias'] ?? '')) ?>" placeholder="Opcional">
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($esKm): ?>
                            <input type="number" name="config[<?= $id ?>][umbral_amarillo_dias]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_amarillo_dias'] ?? '')) ?>" placeholder="Opcional">
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($esKm): ?>
                            <input type="number" name="config[<?= $id ?>][umbral_rojo_dias]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_rojo_dias'] ?? '')) ?>" placeholder="Opcional">
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <label class="form-check">
                                <input type="checkbox" name="config[<?= $id ?>][activo]" value="1" <?= !empty($row['activo']) ? 'checked' : '' ?>>
                                Activo
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <p class="form-hint mt-2">
            Para mantenimiento (km): la alerta se dispara si se alcanza el umbral en <strong>kilometraje o en días</strong> desde el último servicio, lo que ocurra primero.
            Los umbrales en días son opcionales; si no se definen, solo aplica kilometraje.
        </p>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary">Guardar valores por defecto</button>
            <a href="<?= url('alertas') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
