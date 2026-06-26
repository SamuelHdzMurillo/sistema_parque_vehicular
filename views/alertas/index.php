<?php
$pageTitle = 'Alertas';
$modo = $modo ?? 'matriz';
$grupos = $grupos ?? [];
$counts = $counts ?? ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'total' => 0];
$solo_pendientes = $solo_pendientes ?? false;
$vehiculos = $vehiculos ?? [];
$vehiculo_id = isset($vehiculo_id) ? (int) $vehiculo_id : 0;
$totalPendientes = (int) ($counts['total'] ?? 0);
$esMatriz = $modo === 'matriz';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Alertas</h1>
        <p class="page-subtitle">
            <?php if ($esMatriz): ?>
            Mantenimiento y documentos por vehículo — según vencimientos y los km o meses de cada servicio
            <?php elseif ($totalPendientes > 0): ?>
            Historial de alertas atendidas y pendientes
            <?php else: ?>
            Historial de alertas
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if ($esMatriz): ?>
        <?php if ($solo_pendientes): ?>
        <a href="<?= e(alerta_index_url(['pendientes' => null])) ?>" class="btn btn-sm btn-info">Ver todos los vehículos</a>
        <?php else: ?>
        <a href="<?= e(alerta_index_url(['pendientes' => 1])) ?>" class="btn btn-sm btn-warning">Solo con avisos</a>
        <?php endif; ?>
        <a href="<?= e(alerta_index_url(['historial' => 1])) ?>" class="btn btn-sm btn-info">Historial</a>
        <?php else: ?>
        <a href="<?= e(alerta_index_url(['historial' => null, 'todas' => null])) ?>" class="btn btn-sm btn-info">Vista por vehículo</a>
        <?php endif; ?>
    </div>
</div>

<?php App\Core\View::component('alertas-guia'); ?>

<?php if (!empty($vehiculos)): ?>
<div class="card alertas-filtro-vehiculo">
    <form class="filters-bar card-body" method="get" action="<?= url('alertas') ?>">
        <?php if ($solo_pendientes): ?>
        <input type="hidden" name="pendientes" value="1">
        <?php endif; ?>
        <?php if (!$esMatriz): ?>
        <input type="hidden" name="historial" value="1">
        <?php endif; ?>
        <div class="form-group">
            <label class="form-label" for="vehiculo_id">Vehículo</label>
            <select id="vehiculo_id" name="vehiculo_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todos los vehículos</option>
                <?php foreach ($vehiculos as $v): ?>
                <option value="<?= (int) $v['id'] ?>" <?= $vehiculo_id === (int) $v['id'] ? 'selected' : '' ?>>
                    <?= e(catalogo_vehiculo_label($v)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($vehiculo_id > 0): ?>
        <a href="<?= e(alerta_index_url(['vehiculo_id' => null])) ?>" class="btn btn-secondary">Quitar filtro</a>
        <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<?php if ($esMatriz && $totalPendientes > 0): ?>
<div class="alertas-resumen">
    <?php if ((int) ($counts['rojo'] ?? 0) > 0): ?>
    <span class="alertas-resumen-chip alertas-resumen-chip--rojo">
        <?= (int) $counts['rojo'] ?> urgente<?= (int) $counts['rojo'] === 1 ? '' : 's' ?>
    </span>
    <?php endif; ?>
    <?php if ((int) ($counts['amarillo'] ?? 0) > 0): ?>
    <span class="alertas-resumen-chip alertas-resumen-chip--amarillo">
        <?= (int) $counts['amarillo'] ?> atención
    </span>
    <?php endif; ?>
    <?php if ((int) ($counts['verde'] ?? 0) > 0): ?>
    <span class="alertas-resumen-chip alertas-resumen-chip--verde">
        <?= (int) $counts['verde'] ?> aviso<?= (int) $counts['verde'] === 1 ? '' : 's' ?>
    </span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (empty($grupos)): ?>
<div class="card">
    <div class="empty-state py-5 text-center text-muted">
        <?php if ($esMatriz && $solo_pendientes): ?>
        <?php if ($vehiculo_id > 0): ?>
        Este vehículo no tiene avisos pendientes en este momento.
        <?php else: ?>
        Ningún vehículo con avisos pendientes en este momento.
        <?php endif; ?>
        <?php elseif ($esMatriz && $vehiculo_id > 0): ?>
        Vehículo no encontrado o no disponible.
        <?php elseif ($esMatriz): ?>
        No hay vehículos registrados para mostrar.
        <?php else: ?>
        No hay alertas en el historial<?= $vehiculo_id > 0 ? ' para este vehículo' : '' ?>.
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<?php App\Core\View::component('alertas-grupos-list', ['grupos' => $grupos, 'esMatriz' => $esMatriz]); ?>
<?php if ($vehiculo_id <= 0): ?>
<?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
<?php endif; ?>
<?php endif; ?>
