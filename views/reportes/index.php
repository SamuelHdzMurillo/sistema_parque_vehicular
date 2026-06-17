<?php
$pageTitle = 'Reportes';
$tipos = $tipos ?? [
    'inventario' => 'Inventario vehicular',
    'comisiones' => 'Comisiones',
    'mantenimiento' => 'Mantenimiento',
    'combustible' => 'Combustible',
    'danios' => 'Daños',
    'documentacion' => 'Documentación',
    'costos' => 'Costos consolidados',
    'kpi' => 'Indicadores KPI',
];
$tipo = $tipo ?? 'inventario';
$data = $data ?? [];
$rows = $data['data'] ?? $data['rows'] ?? [];
$headers = $data['headers'] ?? ( !empty($rows) && is_array($rows[0] ?? null) ? array_keys($rows[0]) : [] );
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Reportes</h1>
        <p class="page-subtitle">Consulta y exportación de información operativa</p>
    </div>
    <?php if (can('reportes.export')): ?>
    <div class="page-actions">
        <a href="<?= url('reportes/export/' . e($tipo)) ?>?format=csv" class="btn btn-secondary">Exportar CSV</a>
        <a href="<?= url('reportes/export/' . e($tipo)) ?>?format=xlsx" class="btn btn-secondary">Exportar Excel</a>
        <a href="<?= url('reportes/export/' . e($tipo)) ?>?format=pdf" class="btn btn-secondary">Exportar PDF</a>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-2">
    <form class="filters-bar" method="get" action="<?= url('reportes') ?>">
        <div class="form-group">
            <label class="form-label" for="tipo">Tipo de reporte</label>
            <select id="tipo" name="tipo" class="form-select" onchange="this.form.submit()">
                <?php foreach ($tipos as $key => $label): ?>
                <option value="<?= e(is_string($key) ? $key : $label) ?>" <?= $tipo === (is_string($key) ? $key : $label) ? 'selected' : '' ?>>
                    <?= e(is_string($label) ? $label : $key) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h3><?= e(is_string($tipos[$tipo] ?? null) ? $tipos[$tipo] : ucfirst($tipo)) ?></h3>
        <span class="text-muted"><?= count($rows) ?> registros</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <?php foreach ($headers as $h): ?>
                    <th><?= e(ucfirst(str_replace('_', ' ', (string) $h))) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="<?= max(1, count($headers)) ?>" class="text-center text-muted">Sin datos para mostrar</td></tr>
                <?php else: foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($headers as $h): ?>
                    <td><?= e((string) ($row[$h] ?? '—')) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
