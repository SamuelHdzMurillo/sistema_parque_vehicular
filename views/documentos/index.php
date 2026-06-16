<?php $pageTitle = 'Documentos'; $data = $data ?? []; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Documentación vehicular</h1>
        <p class="page-subtitle">Pólizas, verificaciones, tarjetas de circulación y más</p>
    </div>
    <?php if (can('documentos.create')): ?>
    <div class="page-actions"><a href="<?= url('documentos/create') ?>" class="btn btn-primary">+ Subir documento</a></div>
    <?php endif; ?>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Vehículo</th><th>Tipo</th><th>Título</th><th>No. documento</th><th>Vencimiento</th><th>Días rest.</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="7" class="text-center text-muted">Sin documentos</td></tr>
                <?php else: foreach ($data as $d): ?>
                <?php
                $dias = (int) ($d['dias_restantes'] ?? 0);
                $badge = $dias < 0 ? 'badge-danger' : ($dias <= 30 ? 'badge-warning' : 'badge-success');
                ?>
                <tr>
                    <td><?= e($d['numero_economico'] ?? '—') ?></td>
                    <td><?= e(ucfirst(str_replace('_', ' ', $d['tipo']))) ?></td>
                    <td><?= e($d['titulo']) ?></td>
                    <td><?= e($d['numero_documento'] ?? '—') ?></td>
                    <td><?= format_date($d['fecha_vencimiento']) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $dias ?> días</span></td>
                    <td>
                        <a href="<?= url('documentos/' . $d['id'] . '/download') ?>" class="btn btn-sm btn-danger">Descargar</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
