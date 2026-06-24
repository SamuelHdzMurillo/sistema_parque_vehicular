<?php
$pageTitle = 'Configuración de alertas';
$config = alerta_config_sort($config ?? []);
$vehiculos = $vehiculos ?? [];
$vehiculo_id = $vehiculo_id ?? null;
$vehiculo_config = alerta_config_sort($vehiculo_config ?? []);

$configDocs = array_values(array_filter($config, fn ($r) => ($r['unidad'] ?? '') === 'dias'));
$configKm = array_values(array_filter($config, fn ($r) => ($r['unidad'] ?? '') === 'km'));
$configVehDocs = array_values(array_filter($vehiculo_config, fn ($r) => ($r['unidad'] ?? '') === 'dias'));
$configVehKm = array_values(array_filter($vehiculo_config, fn ($r) => ($r['unidad'] ?? '') === 'km'));
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('alertas') ?>">Alertas</a></li><li>/ Ajustes</li></ul>
        <h1 class="page-title">Ajustes de alertas</h1>
        <p class="page-subtitle">Cuándo avisar por mantenimiento o por vencimiento de documentos</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('alertas') ?>" class="btn btn-secondary">Volver</a>
    </div>
</div>

<?php App\Core\View::component('alertas-como-funciona', [
    'configKm' => $configKm,
    'configDocs' => $configDocs,
]); ?>

<div class="card">
    <form action="<?= url('alertas/config') ?>" method="post" class="card-body">
        <?= csrf_field() ?>

        <?php if (empty($config)): ?>
        <p class="text-center text-muted">No hay configuración cargada.</p>
        <?php else: ?>

        <p class="alerta-config-lead">
            Los números de mantenimiento son <strong>kilómetros recorridos desde el último servicio</strong>.
            Los de documentos son <strong>días antes de vencer</strong>.
        </p>

        <?php if (!empty($configKm)): ?>
        <h2 class="alerta-config-seccion">Mantenimiento — cada cuántos km avisar</h2>
        <div class="table-responsive">
            <table class="table alerta-config-table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>En la práctica (con los valores actuales)</th>
                        <th class="text-center" title="Encender o apagar">On</th>
                        <th><span class="badge badge-success">Aviso</span><br><small class="text-muted">km desde servicio</small></th>
                        <th><span class="badge badge-warning">Atención</span><br><small class="text-muted">km desde servicio</small></th>
                        <th><span class="badge badge-danger">Urgente</span><br><small class="text-muted">km desde servicio</small></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($configKm as $row): ?>
                    <?php App\Core\View::component('alerta-config-row', [
                        'row' => $row,
                        'mode' => 'global',
                        'formKey' => (string) ($row['id'] ?? ''),
                    ]); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <details class="alerta-config-extra">
            <summary>Mantenimiento también por días (opcional, avanzado)</summary>
            <p class="text-muted mb-2">Si lo llena, avisa por kilómetros <em>o</em> por días, lo que pase primero.</p>
            <div class="table-responsive">
                <table class="table alerta-config-table alerta-config-table--compact">
                    <thead>
                        <tr>
                            <th>Alerta</th>
                            <th><span class="badge badge-success">Aviso</span></th>
                            <th><span class="badge badge-warning">Atención</span></th>
                            <th><span class="badge badge-danger">Urgente</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configKm as $row): ?>
                        <?php App\Core\View::component('alerta-config-dias-row', [
                            'row' => $row,
                            'mode' => 'global',
                            'formKey' => (string) ($row['id'] ?? ''),
                        ]); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
        <?php endif; ?>

        <?php if (!empty($configDocs)): ?>
        <h2 class="alerta-config-seccion">Documentos — cuántos días antes avisar</h2>
        <div class="table-responsive">
            <table class="table alerta-config-table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>En la práctica (con los valores actuales)</th>
                        <th class="text-center" title="Encender o apagar">On</th>
                        <th><span class="badge badge-success">Aviso</span><br><small class="text-muted">días antes</small></th>
                        <th><span class="badge badge-warning">Atención</span><br><small class="text-muted">días antes</small></th>
                        <th><span class="badge badge-danger">Urgente</span><br><small class="text-muted">días antes</small></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($configDocs as $row): ?>
                    <?php App\Core\View::component('alerta-config-row', [
                        'row' => $row,
                        'mode' => 'global',
                        'formKey' => (string) ($row['id'] ?? ''),
                    ]); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="alerta-config-actions">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<details class="card alerta-config-vehiculo-panel">
    <summary class="card-header alerta-config-vehiculo-summary">
        <strong>Reglas solo para un vehículo</strong>
        <span class="text-muted">— opcional, la mayoría no lo necesita</span>
    </summary>
    <div class="card-body">
        <form method="get" action="<?= url('alertas/config') ?>" class="mb-3">
            <label class="form-label" for="vehiculo_id">Vehículo</label>
            <select id="vehiculo_id" name="vehiculo_id" class="form-select" style="max-width:420px" onchange="this.form.submit()">
                <option value="">— Ninguno —</option>
                <?php foreach ($vehiculos as $v): ?>
                <option value="<?= (int) $v['id'] ?>" <?= (int) $vehiculo_id === (int) $v['id'] ? 'selected' : '' ?>>
                    <?= e(catalogo_vehiculo_label($v)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (!empty($vehiculo_id) && !empty($vehiculo_config)): ?>
        <form action="<?= url('alertas/config') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="vehiculo_id" value="<?= (int) $vehiculo_id ?>">
            <p class="alerta-config-lead">Marque <strong>On</strong> solo en las filas que quiera cambiar para este vehículo.</p>

            <?php if (!empty($configVehKm)): ?>
            <h3 class="alerta-config-seccion alerta-config-seccion--sm">Mantenimiento</h3>
            <div class="table-responsive">
                <table class="table alerta-config-table">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>En la práctica</th>
                            <th class="text-center">Cambiar</th>
                            <th><span class="badge badge-success">Aviso</span></th>
                            <th><span class="badge badge-warning">Atención</span></th>
                            <th><span class="badge badge-danger">Urgente</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configVehKm as $row): ?>
                        <?php App\Core\View::component('alerta-config-row', [
                            'row' => $row,
                            'mode' => 'vehiculo',
                            'formKey' => $row['tipo'] ?? '',
                        ]); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($configVehDocs)): ?>
            <h3 class="alerta-config-seccion alerta-config-seccion--sm">Documentos</h3>
            <div class="table-responsive">
                <table class="table alerta-config-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>En la práctica</th>
                            <th class="text-center">Cambiar</th>
                            <th><span class="badge badge-success">Aviso</span></th>
                            <th><span class="badge badge-warning">Atención</span></th>
                            <th><span class="badge badge-danger">Urgente</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configVehDocs as $row): ?>
                        <?php App\Core\View::component('alerta-config-row', [
                            'row' => $row,
                            'mode' => 'vehiculo',
                            'formKey' => $row['tipo'] ?? '',
                        ]); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary mt-2">Guardar de este vehículo</button>
        </form>
        <?php elseif (!empty($vehiculo_id)): ?>
        <p class="text-muted">Sin alertas configurables.</p>
        <?php endif; ?>
    </div>
</details>
