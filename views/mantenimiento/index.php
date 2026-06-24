<?php $pageTitle = 'Mantenimiento'; $data = $data ?? []; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Mantenimiento</h1>
        <p class="page-subtitle">Servicios preventivos, correctivos y predictivos</p>
    </div>
    <?php if (can('mantenimiento.create')): ?>
    <div class="page-actions"><a href="<?= url('mantenimiento/create') ?>" class="btn btn-primary">+ Registrar servicio</a></div>
    <?php endif; ?>
</div>
<div class="card">
    <form class="filters-bar" method="get">
        <div class="form-group">
            <label class="form-label" for="estado">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">Todos</option>
                <?php foreach (['pendiente','programado','autorizado','en_proceso','finalizado','cancelado'] as $est): ?>
                <option value="<?= e($est) ?>" <?= ($_GET['estado'] ?? '') === $est ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $est))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-info">Filtrar</button>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Folio</th><th>Vehículo</th><th>Servicio</th><th>Tipo</th><th>Fecha</th><th>Proveedor</th><th>Costo</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="9" class="text-center text-muted">Sin registros</td></tr>
                <?php else: foreach ($data as $m): ?>
                <tr>
                    <td><strong><?= e($m['folio']) ?></strong></td>
                    <td><?= e($m['numero_economico'] ?? '—') ?></td>
                    <td><?= e(mantenimiento_servicios_labels($m['servicios'] ?? (!empty($m['servicio']) ? [(string) $m['servicio']] : []))) ?></td>
                    <td><?= e(ucfirst($m['tipo'])) ?></td>
                    <td><?= format_date($m['fecha']) ?></td>
                    <td><?= e($m['proveedor_nombre'] ?? $m['razon_social'] ?? '—') ?></td>
                    <td><?= format_money($m['costo'] ?? 0) ?></td>
                    <td><span class="badge badge-secondary"><?= e(str_replace('_', ' ', $m['estado'])) ?></span></td>
                    <td><a href="<?= url('mantenimiento/' . $m['id']) ?>" class="btn btn-sm btn-info">Ver</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
