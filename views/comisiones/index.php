<?php
$pageTitle = 'Comisiones';
$comEstados = ['borrador' => 'Borrador', 'en_curso' => 'En curso', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
$data = $data ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Comisiones</h1>
        <p class="page-subtitle">Órdenes de salida y control de viajes institucionales</p>
    </div>
    <?php if (can('comisiones.create')): ?>
    <div class="page-actions"><a href="<?= url('comisiones/create') ?>" class="btn btn-primary">+ Nueva comisión</a></div>
    <?php endif; ?>
</div>

<div class="card">
    <form class="filters-bar" method="get">
        <div class="form-group">
            <label class="form-label" for="estado">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($comEstados as $val => $label): ?>
                <option value="<?= e($val) ?>" <?= ($_GET['estado'] ?? '') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Folio</th><th>Fecha</th><th>Vehículo</th><th>Destino</th>
                    <th>Conductor</th><th>Km recorridos</th><th>Estado</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="8" class="text-center text-muted">No hay comisiones</td></tr>
                <?php else: foreach ($data as $c): ?>
                <tr>
                    <td><strong><?= e($c['folio']) ?></strong></td>
                    <td><?= format_date($c['fecha']) ?></td>
                    <td><?= e($c['numero_economico'] ?? '—') ?></td>
                    <td><?= e(mb_substr($c['destino'], 0, 40)) ?><?= mb_strlen($c['destino']) > 40 ? '…' : '' ?></td>
                    <td><?= e($c['conductor_nombre'] ?? '—') ?></td>
                    <td><?= $c['km_recorridos'] !== null ? number_format((int) $c['km_recorridos']) : '—' ?></td>
                    <td><span class="badge badge-secondary"><?= e($comEstados[$c['estado']] ?? $c['estado']) ?></span></td>
                    <td><a href="<?= url('comisiones/' . $c['id']) ?>" class="btn btn-sm btn-secondary">Ver</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
