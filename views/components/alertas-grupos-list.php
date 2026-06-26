<?php
$grupos = $grupos ?? [];
$esMatriz = $esMatriz ?? true;
?>
<div class="alertas-list">
    <?php foreach ($grupos as $grupo): ?>
    <?php
    $nivelGrupo = (string) ($grupo['nivel_max'] ?? '');
    $alertasGrupo = $grupo['alertas'] ?? [];
    $totalGrupo = count($alertasGrupo);
    $kmVehiculo = (int) ($grupo['kilometraje_actual'] ?? 0);
    ?>
    <section class="card alertas-vehiculo<?= $nivelGrupo !== '' ? ' alertas-vehiculo--' . e($nivelGrupo) : '' ?>">
        <div class="alertas-vehiculo-header">
            <div class="alertas-vehiculo-info">
                <h2 class="alertas-vehiculo-nombre"><?= e($grupo['numero_economico']) ?></h2>
                <p class="alertas-vehiculo-meta">
                    <?php if ($esMatriz): ?>
                    <?= number_format($kmVehiculo, 0, '.', ',') ?> km actuales · <?= $totalGrupo ?> aviso<?= $totalGrupo === 1 ? '' : 's' ?>
                    <?php else: ?>
                    <?= $totalGrupo ?> alerta<?= $totalGrupo === 1 ? '' : 's' ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($nivelGrupo !== ''): ?>
            <span class="badge <?= semaforo_class($nivelGrupo) ?>"><?= e(alerta_nivel_label($nivelGrupo)) ?></span>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table alertas-grupo-tabla">
                <thead>
                    <tr>
                        <th>Servicio / Documento</th>
                        <th class="alertas-col-prioridad">Estado</th>
                        <th class="alertas-col-fecha">Último mantenimiento</th>
                        <th class="alertas-col-fecha">Próximo toca</th>
                        <th class="alertas-col-accion"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alertasGrupo as $a): ?>
                    <?php
                    $atendida = !empty($a['atendida']);
                    $esMantenimiento = ($a['categoria'] ?? '') === 'mantenimiento';
                    $sinAlta = !empty($a['sin_alta']);
                    $estado = $esMantenimiento
                        ? alerta_estado_mantenimiento($a)
                        : alerta_estado_documento($a);
                    $proximaVencida = !empty($a['fecha_proximo_mantenimiento'])
                        && $a['fecha_proximo_mantenimiento'] < date('Y-m-d')
                        && !$sinAlta;
                    $accionUrl = alerta_accion_url($a);
                    $resumen = alerta_resumen_fila($a);
                    $puedeRegistrar = $esMantenimiento && can('mantenimiento.create');
                    $puedeAtenderDoc = !$esMantenimiento && can('documentos.read') && !$sinAlta && ($a['nivel'] ?? null) !== null;
                    $mostrarAtender = !$atendida && (
                        ($esMantenimiento && !$sinAlta && ($a['nivel'] ?? null) !== null && $puedeRegistrar)
                        || $puedeAtenderDoc
                    );
                    $mostrarRegistrar = $esMantenimiento && $sinAlta && $puedeRegistrar;
                    ?>
                    <tr class="alertas-grupo-fila<?= $atendida ? ' alertas-grupo-fila--atendida' : '' ?><?= $sinAlta ? ' alertas-grupo-fila--sin-alta' : '' ?>">
                        <td class="alertas-celda-servicio">
                            <span class="alertas-servicio-nombre"><?= e($a['servicio_nombre'] ?? $a['titulo']) ?></span>
                            <?php if ($resumen !== '' && !$sinAlta): ?>
                            <span class="alertas-servicio-detalle"><?= e($resumen) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($a['mantenimiento_abierto_folio']) && !$atendida): ?>
                            <a class="alertas-servicio-en-curso" href="<?= url('mantenimiento/' . (int) $a['mantenimiento_abierto_id']) ?>">
                                En curso: <?= e($a['mantenimiento_abierto_folio']) ?>
                            </a>
                            <?php endif; ?>
                        </td>
                        <td class="alertas-col-prioridad">
                            <span class="badge <?= e($estado['class']) ?>"><?= e($estado['label']) ?></span>
                        </td>
                        <td class="alertas-col-fecha<?= $sinAlta ? ' text-muted' : '' ?>">
                            <?php if ($esMantenimiento): ?>
                            <?php if ($sinAlta): ?>
                            <span class="alertas-sin-alta">Sin alta</span>
                            <?php elseif (!empty($a['mantenimiento_id']) && can('mantenimiento.read')): ?>
                            <a href="<?= url('mantenimiento/' . (int) $a['mantenimiento_id']) ?>" title="<?= e($a['mantenimiento_folio'] ?? '') ?>">
                                <?= e(alerta_ultimo_mantenimiento_display($a)) ?>
                            </a>
                            <?php else: ?>
                            <?= e(alerta_ultimo_mantenimiento_display($a)) ?>
                            <?php endif; ?>
                            <?php else: ?>
                            <?php if (!empty($a['documento_titulo'])): ?>
                            <span title="<?= e($a['documento_titulo']) ?>"><?= e($a['documento_titulo']) ?></span>
                            <?php elseif (!empty($a['fecha_ultimo_mantenimiento'])): ?>
                            <?= e(format_date($a['fecha_ultimo_mantenimiento'])) ?>
                            <?php else: ?>
                            <span class="text-muted">Documento</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="alertas-col-fecha alertas-celda-proximo<?= $proximaVencida ? ' alertas-celda-proximo--vencida' : '' ?><?= $sinAlta ? ' text-muted' : '' ?>">
                            <?php if ($esMantenimiento): ?>
                            <?php if ($sinAlta): ?>
                            <span class="alertas-sin-alta-hint">—</span>
                            <?php elseif ($a['fecha_proximo_mantenimiento'] ?? null || $a['proximo_km'] ?? null): ?>
                            <?php $proximo = alerta_proximo_partes($a); ?>
                            <?php if ($proximo['fecha'] !== null): ?>
                            <span class="alertas-proximo-fecha"><?= e($proximo['fecha']) ?></span>
                            <?php endif; ?>
                            <?php if ($proximo['km'] !== null): ?>
                            <span class="alertas-proximo-km"><?= e($proximo['km']) ?></span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                            <?php else: ?>
                            <?= e(alerta_proximo_display($a)) ?>
                            <?php endif; ?>
                        </td>
                        <td class="alertas-col-accion">
                            <?php if ($atendida): ?>
                            <span class="badge badge-success">Atendida</span>
                            <?php elseif ($mostrarRegistrar): ?>
                            <a href="<?= e($accionUrl) ?>" class="btn btn-sm btn-secondary">Registrar</a>
                            <?php elseif ($mostrarAtender): ?>
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
