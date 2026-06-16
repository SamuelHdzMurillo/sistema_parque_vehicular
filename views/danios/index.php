<?php $pageTitle = 'Daños'; $data = $data ?? []; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Reporte de daños</h1>
        <p class="page-subtitle">Incidencias y seguimiento de reparaciones</p>
    </div>
    <?php if (can('danios.create')): ?>
    <div class="page-actions"><a href="<?= url('danios/create') ?>" class="btn btn-primary">+ Reportar daño</a></div>
    <?php endif; ?>
</div>
<div class="card">
    <form class="filters-bar" method="get">
        <div class="form-group">
            <label class="form-label" for="estado">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="">Todos</option>
                <?php foreach (['reportado','en_evaluacion','en_reparacion','reparado','cerrado_sin_accion'] as $est): ?>
                <option value="<?= e($est) ?>" <?= ($_GET['estado'] ?? '') === $est ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $est))) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-info">Filtrar</button>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Vehículo</th><th>Tipo</th><th>Ubicación</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="6" class="text-center text-muted">Sin registros</td></tr>
                <?php else: foreach ($data as $d): ?>
                <tr>
                    <td><?= format_datetime($d['created_at']) ?></td>
                    <td><?= e($d['numero_economico'] ?? '—') ?></td>
                    <td><?= e($d['tipo_dano']) ?></td>
                    <td><?= e($d['ubicacion']) ?></td>
                    <td><span class="badge badge-warning"><?= e(str_replace('_', ' ', $d['estado'])) ?></span></td>
                    <td><a href="<?= url('danios/' . $d['id']) ?>" class="btn btn-sm btn-info">Ver</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
