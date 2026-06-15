<?php
$pageTitle = 'Herramientas — ' . ($vehiculo['numero_economico'] ?? '');
$v = $vehiculo ?? [];
$herramientas = $herramientas ?? [];
$estadosHerr = ['presente' => 'Presente', 'faltante' => 'Faltante', 'dañado' => 'Dañado', 'vencido' => 'Vencido'];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('vehiculos') ?>">Vehículos</a></li>
            <li><a href="<?= url('vehiculos/' . ($v['id'] ?? '')) ?>"><?= e($v['numero_economico'] ?? '') ?></a></li>
            <li>/ Herramientas</li>
        </ul>
        <h1 class="page-title">Inventario de herramientas</h1>
        <p class="page-subtitle">Vehículo <?= e($v['numero_economico'] ?? '') ?> — Placas <?= e($v['placas'] ?? '') ?></p>
    </div>
</div>

<div class="card">
    <?php if (can('herramientas.update')): ?>
    <form action="<?= url('herramientas/vehiculo/' . $v['id']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Herramienta / Equipo</th><th>Estado</th><th>Vencimiento</th><th>Observaciones</th></tr></thead>
                <tbody>
                    <?php if (empty($herramientas)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Sin herramientas registradas</td></tr>
                    <?php else: foreach ($herramientas as $h): ?>
                    <tr>
                        <td><strong><?= e(ucfirst(str_replace('_', ' ', $h['tipo']))) ?></strong></td>
                        <td>
                            <select name="herramientas[<?= e($h['tipo']) ?>]" class="form-select">
                                <?php foreach ($estadosHerr as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= ($h['estado'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><?= format_date($h['fecha_vencimiento'] ?? null) ?></td>
                        <td><?= e($h['observaciones'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Guardar inventario</button>
            <a href="<?= url('vehiculos/' . $v['id']) ?>" class="btn btn-secondary">Volver al expediente</a>
        </div>
    </form>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Herramienta</th><th>Estado</th><th>Vencimiento</th><th>Observaciones</th></tr></thead>
            <tbody>
                <?php foreach ($herramientas as $h): ?>
                <tr>
                    <td><?= e(ucfirst(str_replace('_', ' ', $h['tipo']))) ?></td>
                    <td><span class="badge <?= ($h['estado'] ?? '') === 'presente' ? 'badge-success' : 'badge-warning' ?>"><?= e($estadosHerr[$h['estado']] ?? $h['estado']) ?></span></td>
                    <td><?= format_date($h['fecha_vencimiento'] ?? null) ?></td>
                    <td><?= e($h['observaciones'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
