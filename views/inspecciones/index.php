<?php $pageTitle = 'Inspecciones'; $data = $data ?? []; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Inspecciones</h1>
        <p class="page-subtitle">Checklist de 11 puntos y control de condiciones del vehículo</p>
    </div>
    <?php if (can('inspecciones.create')): ?>
    <div class="page-actions"><a href="<?= url('inspecciones/create') ?>" class="btn btn-primary">+ Nueva inspección</a></div>
    <?php endif; ?>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Vehículo</th><th>Responsable</th><th>Km</th><th>Resultado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="6" class="text-center text-muted">Sin inspecciones</td></tr>
                <?php else: foreach ($data as $i): ?>
                <tr>
                    <td><?= format_date($i['fecha']) ?></td>
                    <td><?= e($i['numero_economico'] ?? '—') ?></td>
                    <td><?= e($i['responsable_nombre'] ?? '—') ?></td>
                    <td><?= number_format((int) ($i['kilometraje'] ?? 0)) ?></td>
                    <td><span class="badge <?= ($i['resultado_general'] ?? '') === 'aprobada' ? 'badge-success' : (($i['resultado_general'] ?? '') === 'rechazada' ? 'badge-danger' : 'badge-warning') ?>"><?= e(ucfirst($i['resultado_general'] ?? '')) ?></span></td>
                    <td><a href="<?= url('inspecciones/' . $i['id']) ?>" class="btn btn-sm btn-info">Ver</a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
