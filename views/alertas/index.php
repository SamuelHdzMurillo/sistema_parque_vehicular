<?php $pageTitle = 'Alertas'; $data = $data ?? []; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Centro de alertas</h1>
        <p class="page-subtitle">Notificaciones de vencimientos, mantenimiento y condiciones críticas</p>
    </div>
    <div class="page-actions">
        <?php if (can('alertas.config')): ?>
        <a href="<?= url('alertas/config') ?>" class="btn btn-secondary">Configuración</a>
        <?php endif; ?>
        <a href="<?= url('alertas?todas=1') ?>" class="btn btn-link">Ver todas</a>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Nivel</th><th>Vehículo</th><th>Título</th><th>Mensaje</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="7" class="text-center text-muted">No hay alertas pendientes</td></tr>
                <?php else: foreach ($data as $a): ?>
                <tr>
                    <td><span class="badge <?= semaforo_class($a['nivel'] ?? null) ?>"><?= e(ucfirst((string) ($a['nivel'] ?? '—'))) ?></span></td>
                    <td><?= e($a['numero_economico'] ?? '—') ?></td>
                    <td><?= e($a['titulo']) ?></td>
                    <td><?= e(mb_substr($a['mensaje'], 0, 80)) ?><?= mb_strlen($a['mensaje']) > 80 ? '…' : '' ?></td>
                    <td><?= format_datetime($a['created_at']) ?></td>
                    <td><?= !empty($a['atendida']) ? '<span class="badge badge-success">Atendida</span>' : '<span class="badge badge-warning">Pendiente</span>' ?></td>
                    <td>
                        <?php if (empty($a['atendida']) && can('alertas.read')): ?>
                        <form action="<?= url('alertas/' . $a['id'] . '/atender') ?>" method="post" class="d-flex gap-1">
                            <?= csrf_field() ?>
                            <input type="text" name="comentario" class="form-control" placeholder="Comentario" style="max-width:140px">
                            <button type="submit" class="btn btn-sm btn-primary">Atender</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
