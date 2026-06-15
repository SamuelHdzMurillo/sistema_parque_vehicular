<?php $pageTitle = 'Auditoría'; $data = $data ?? []; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Auditoría del sistema</h1>
        <p class="page-subtitle">Registro de acciones y cambios en el sistema</p>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Módulo</th>
                    <th>Registro ID</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="6" class="text-center text-muted">Sin registros de auditoría</td></tr>
                <?php else: foreach ($data as $log): ?>
                <tr>
                    <td><?= format_datetime($log['created_at'] ?? null) ?></td>
                    <td><?= e($log['usuario'] ?? 'Sistema') ?></td>
                    <td><span class="badge badge-info"><?= e($log['accion'] ?? '') ?></span></td>
                    <td><?= e($log['modulo'] ?? '') ?></td>
                    <td><?= e((string) ($log['registro_id'] ?? '—')) ?></td>
                    <td><?= e($log['ip_address'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 30]); ?>
</div>
