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
            <?= count($grupos) ?> vehículo<?= count($grupos) === 1 ? '' : 's' ?> · <?= $totalPendientes ?> alerta<?= $totalPendientes === 1 ? '' : 's' ?>
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

<?php App\Core\View::component('alertas-guia'); ?>

<?php if ($solo_pendientes && $totalPendientes > 0): ?>
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
        <?php if ($solo_pendientes): ?>
        Nada pendiente. Para mantenimientos, registre primero el servicio en Mantenimiento; las alertas aparecerán cuando llegue el km o los días configurados.
        <?php else: ?>
        No hay alertas registradas.
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alertas-list">
    <?php foreach ($grupos as $grupo): ?>
    <?php
    $nivelGrupo = (string) ($grupo['nivel_max'] ?? '');
    $alertasGrupo = $grupo['alertas'] ?? [];
    $totalGrupo = count($alertasGrupo);
    ?>
    <section class="card alertas-vehiculo alertas-vehiculo--<?= e($nivelGrupo) ?>">
        <div class="alertas-vehiculo-header">
            <div class="alertas-vehiculo-info">
                <h2 class="alertas-vehiculo-nombre"><?= e($grupo['numero_economico']) ?></h2>
                <p class="alertas-vehiculo-meta">
                    <?= $totalGrupo ?> servicio<?= $totalGrupo === 1 ? '' : 's' ?> pendiente<?= $totalGrupo === 1 ? '' : 's' ?>
                </p>
            </div>
            <span class="badge <?= semaforo_class($nivelGrupo) ?>"><?= e(alerta_nivel_label($nivelGrupo)) ?></span>
        </div>
        <div class="table-responsive">
            <table class="table alertas-grupo-tabla">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th class="alertas-col-prioridad">Prioridad</th>
                        <th class="alertas-col-fecha">Último</th>
                        <th class="alertas-col-fecha">Próximo toca</th>
                        <th class="alertas-col-accion"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alertasGrupo as $a): ?>
                    <?php
                    $nivel = (string) ($a['nivel'] ?? '');
                    $atendida = !empty($a['atendida']);
                    $fechaUltima = $a['fecha_ultimo_mantenimiento'] ?? null;
                    $proximo = alerta_proximo_partes($a);
                    $proximaVencida = !empty($a['fecha_proximo_mantenimiento'])
                        && $a['fecha_proximo_mantenimiento'] < date('Y-m-d');
                    $accionUrl = alerta_accion_url($a);
                    $resumen = alerta_resumen_fila($a);
                    $esMantenimiento = ($a['categoria'] ?? '') === 'mantenimiento';
                    $puedeAtender = $esMantenimiento ? can('mantenimiento.create') : can('documentos.read');
                    ?>
                    <tr class="alertas-grupo-fila<?= $atendida ? ' alertas-grupo-fila--atendida' : '' ?>">
                        <td class="alertas-celda-servicio">
                            <span class="alertas-servicio-nombre"><?= e($a['servicio_nombre'] ?? $a['titulo']) ?></span>
                            <?php if ($resumen !== ''): ?>
                            <span class="alertas-servicio-detalle"><?= e($resumen) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($a['mantenimiento_abierto_folio']) && !$atendida): ?>
                            <a class="alertas-servicio-en-curso" href="<?= url('mantenimiento/' . (int) $a['mantenimiento_abierto_id']) ?>">
                                En curso: <?= e($a['mantenimiento_abierto_folio']) ?>
                            </a>
                            <?php endif; ?>
                        </td>
                        <td class="alertas-col-prioridad">
                            <span class="badge <?= semaforo_class($nivel) ?>"><?= e(alerta_nivel_label($nivel)) ?></span>
                        </td>
                        <td class="alertas-col-fecha text-muted">
                            <?php if (!empty($a['mantenimiento_id']) && can('mantenimiento.read')): ?>
                            <a href="<?= url('mantenimiento/' . (int) $a['mantenimiento_id']) ?>" title="<?= e($a['mantenimiento_folio'] ?? '') ?>">
                                <?= format_date($fechaUltima) ?>
                            </a>
                            <?php else: ?>
                            <?= format_date($fechaUltima) ?>
                            <?php endif; ?>
                        </td>
                        <td class="alertas-col-fecha alertas-celda-proximo<?= $proximaVencida && !$atendida ? ' alertas-celda-proximo--vencida' : '' ?>">
                            <?php if ($proximo['fecha'] !== null || $proximo['km'] !== null): ?>
                            <?php if ($proximo['fecha'] !== null): ?>
                            <span class="alertas-proximo-fecha"><?= e($proximo['fecha']) ?></span>
                            <?php endif; ?>
                            <?php if ($proximo['km'] !== null): ?>
                            <span class="alertas-proximo-km"><?= e($proximo['km']) ?></span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="alertas-col-accion">
                            <?php if ($atendida): ?>
                            <span class="badge badge-success">Atendida</span>
                            <?php elseif ($puedeAtender): ?>
                            <a href="<?= e($accionUrl) ?>" class="btn btn-sm btn-primary">Atender</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endforeach; ?>
</div>
<?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
<?php endif; ?>
