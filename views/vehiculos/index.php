<?php
$pageTitle = 'Vehículos';
$data = $data ?? [];
$estadosLabel = [
    'activo' => 'Activo', 'disponible' => 'Disponible', 'en_comision' => 'En comisión',
    'en_mantenimiento' => 'En mantenimiento', 'en_taller' => 'En taller',
    'fuera_servicio' => 'Fuera de servicio', 'baja' => 'Baja',
];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Vehículos</h1>
        <p class="page-subtitle">Inventario y gestión del parque vehicular</p>
    </div>
    <?php if (can('vehiculos.create')): ?>
    <div class="page-actions">
        <a href="<?= url('vehiculos/create') ?>" class="btn btn-primary">+ Registrar vehículo</a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <form class="filters-bar" method="get" action="<?= url('vehiculos') ?>">
        <div class="form-group">
            <label class="form-label" for="q">Buscar</label>
            <input type="search" id="q" name="q" class="form-control" placeholder="Identificador, placas, VIN…"
                   value="<?= e($_GET['q'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="estado">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($estadosLabel as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= ($_GET['estado'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-info">Filtrar</button>
        <a href="<?= url('vehiculos') ?>" class="btn btn-secondary">Limpiar</a>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><?= e(vehiculo_identificador_label()) ?></th>
                    <th>Marca / Modelo</th>
                    <th>Placas</th>
                    <th>Área</th>
                    <th>Responsable</th>
                    <th>Km actual</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="8" class="text-center text-muted">No hay vehículos registrados</td></tr>
                <?php else: ?>
                <?php foreach ($data as $v): ?>
                <tr>
                    <td><strong><?= e($v['numero_economico']) ?></strong></td>
                    <td><?= e($v['marca'] . ' ' . $v['modelo']) ?></td>
                    <td><?= e($v['placas']) ?></td>
                    <td><?= e($v['area_nombre'] ?? '—') ?></td>
                    <td><?= e($v['responsable_nombre'] ?? '—') ?></td>
                    <td><?= number_format((int) ($v['kilometraje_actual'] ?? 0)) ?> km</td>
                    <td>
                        <span class="badge <?= vehiculo_estado_badge($v['estado']) ?>">
                            <?= e($estadosLabel[$v['estado']] ?? $v['estado']) ?>
                        </span>
                    </td>
                    <td class="table-actions">
                        <?php if (can('expediente.read')): ?>
                        <a href="<?= url('vehiculos/' . $v['id']) ?>" class="btn btn-sm btn-info">Expediente</a>
                        <?php endif; ?>
                        <?php if (can('vehiculos.update')): ?>
                        <a href="<?= url('vehiculos/' . $v['id'] . '/edit') ?>" class="btn btn-sm btn-accent">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
