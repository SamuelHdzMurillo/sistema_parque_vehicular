<?php
$pageTitle = 'Alertas';
$data = $data ?? [];
$grupos = $grupos ?? alerta_agrupar_por_vehiculo($data);
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
            · <?= count($grupos) ?> vehículo<?= count($grupos) === 1 ? '' : 's' ?>
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
                    <th style="width:120px">Vehículo</th>
                    <th>Servicio</th>
                    <th style="width:90px">Prioridad</th>
                    <th style="width:110px">Último servicio</th>
                    <th style="width:110px">Próximo toca</th>
                    <th style="width:140px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($grupos)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <?= $solo_pendientes ? 'Nada pendiente. Todo en orden.' : 'No hay alertas registradas.' ?>
                    </td>
                </tr>
                <?php else: foreach ($grupos as $grupo): ?>
                <?php
                $nivelGrupo = (string) ($grupo['nivel_max'] ?? '');
                $alertasGrupo = $grupo['alertas'] ?? [];
                $totalGrupo = count($alertasGrupo);
                $primera = true;
                ?>
                <?php foreach ($alertasGrupo as $a): ?>
                <?php
                $nivel = (string) ($a['nivel'] ?? '');
                $atendida = !empty($a['atendida']);
                $fechaUltima = $a['fecha_ultimo_mantenimiento'] ?? null;
                $fechaProxima = $a['fecha_proximo_mantenimiento'] ?? null;
                $proximaVencida = $fechaProxima !== null && $fechaProxima !== '' && $fechaProxima < date('Y-m-d');
                $accionUrl = alerta_accion_url($a);
                $resumen = alerta_resumen_fila($a);
                $esMantenimiento = ($a['categoria'] ?? '') === 'mantenimiento';
                $puedeAtender = $esMantenimiento ? can('mantenimiento.create') : can('documentos.read');
                ?>
                <tr class="alertas-tabla-fila alertas-tabla-fila--<?= e($nivel) ?><?= $atendida ? ' alertas-tabla-fila--atendida' : '' ?><?= !$primera ? ' alertas-tabla-fila--sub' : '' ?>">
                    <?php if ($primera): ?>
                    <td rowspan="<?= $totalGrupo ?>" class="alertas-grupo-vehiculo alertas-grupo-vehiculo--<?= e($nivelGrupo) ?>">
                        <strong><?= e($grupo['numero_economico']) ?></strong>
                        <?php if ($totalGrupo > 1): ?>
                        <div class="alertas-grupo-count"><?= $totalGrupo ?> alertas</div>
                        <?php endif; ?>
                    </td>
                    <?php $primera = false; endif; ?>
                    <td>
                        <?= e($a['servicio_nombre'] ?? $a['titulo']) ?>
                        <?php if ($resumen !== ''): ?>
                        <div class="alertas-tabla-detalle"><?= e($resumen) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($a['mantenimiento_abierto_folio']) && !$atendida): ?>
                        <div class="alertas-tabla-detalle">
                            <a href="<?= url('mantenimiento/' . (int) $a['mantenimiento_abierto_id']) ?>">
                                En curso: <?= e($a['mantenimiento_abierto_folio']) ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= semaforo_class($nivel) ?>"><?= e(alerta_nivel_label($nivel)) ?></span>
                    </td>
                    <td class="text-muted">
                        <?php if (!empty($a['mantenimiento_id']) && can('mantenimiento.read')): ?>
                        <a href="<?= url('mantenimiento/' . (int) $a['mantenimiento_id']) ?>" title="<?= e($a['mantenimiento_folio'] ?? '') ?>">
                            <?= format_date($fechaUltima) ?>
                        </a>
                        <?php else: ?>
                        <?= format_date($fechaUltima) ?>
                        <?php endif; ?>
                    </td>
                    <td class="<?= $proximaVencida && !$atendida ? 'text-danger' : 'text-muted' ?>">
                        <?= format_date($fechaProxima) ?>
                    </td>
                    <td>
                        <?php if ($atendida): ?>
                        <span class="badge badge-success">Atendida</span>
                        <?php elseif ($puedeAtender): ?>
                        <a href="<?= e($accionUrl) ?>" class="btn btn-sm btn-primary">Atender</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
