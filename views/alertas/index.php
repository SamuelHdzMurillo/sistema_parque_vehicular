<?php
$pageTitle = 'Alertas';
$data = $data ?? [];
$counts = $counts ?? ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'total' => 0];
$solo_pendientes = $solo_pendientes ?? true;
$totalPendientes = (int) ($counts['total'] ?? 0);
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Alertas</h1>
        <p class="page-subtitle">
            <?php if ($solo_pendientes && $totalPendientes > 0): ?>
            <?= $totalPendientes ?> pendiente<?= $totalPendientes === 1 ? '' : 's' ?>
            · <?= (int) ($counts['rojo'] ?? 0) ?> urgente<?= (int) ($counts['rojo'] ?? 0) === 1 ? '' : 's' ?>
            <?php else: ?>
            Mantenimientos y documentos por revisar
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if (can('alertas.config')): ?>
        <a href="<?= url('alertas/config') ?>" class="btn btn-secondary">Ajustes</a>
        <?php endif; ?>
        <?php if ($solo_pendientes): ?>
        <a href="<?= url('alertas?todas=1') ?>" class="btn btn-sm btn-info">Ver historial</a>
        <?php else: ?>
        <a href="<?= url('alertas') ?>" class="btn btn-sm btn-info">Solo pendientes</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table alertas-tabla">
            <thead>
                <tr>
                    <th style="width:90px">Prioridad</th>
                    <th>Qué pasa</th>
                    <th style="width:120px">Vehículo</th>
                    <th style="width:130px">Fecha</th>
                    <th style="width:140px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <?= $solo_pendientes ? 'Nada pendiente. Todo en orden.' : 'No hay alertas registradas.' ?>
                    </td>
                </tr>
                <?php else: foreach ($data as $a): ?>
                <?php
                $nivel = (string) ($a['nivel'] ?? '');
                $atendida = !empty($a['atendida']);
                ?>
                <tr class="alertas-tabla-fila alertas-tabla-fila--<?= e($nivel) ?><?= $atendida ? ' alertas-tabla-fila--atendida' : '' ?>">
                    <td>
                        <span class="badge <?= semaforo_class($nivel) ?>"><?= e(alerta_nivel_label($nivel)) ?></span>
                    </td>
                    <td>
                        <strong><?= e($a['titulo']) ?></strong>
                        <div class="alertas-tabla-detalle"><?= e($a['mensaje']) ?></div>
                    </td>
                    <td><?= e($a['numero_economico'] ?? '—') ?></td>
                    <td class="text-muted"><?= format_datetime($a['created_at']) ?></td>
                    <td>
                        <?php if ($atendida): ?>
                        <span class="badge badge-success">Atendida</span>
                        <?php elseif (can('alertas.read')): ?>
                        <form action="<?= url('alertas/' . $a['id'] . '/atender') ?>" method="post" class="alertas-tabla-accion">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-primary">Listo</button>
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
