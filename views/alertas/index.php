<?php
$pageTitle = 'Alertas';
$modo = $modo ?? 'matriz';
$grupos = $grupos ?? [];
$counts = $counts ?? ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'total' => 0];
$solo_pendientes = $solo_pendientes ?? false;
$vehiculos = $vehiculos ?? [];
$vehiculo_id = isset($vehiculo_id) ? (int) $vehiculo_id : 0;
$totalPendientes = (int) ($counts['total'] ?? 0);
$esMatriz = $modo === 'matriz';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Alertas</h1>
        <p class="page-subtitle">
            <?php if ($esMatriz): ?>
            Mantenimiento por vehículo — último servicio y cuándo toca otra vez
            <?php elseif ($totalPendientes > 0): ?>
            Historial de alertas atendidas y pendientes
            <?php else: ?>
            Historial de alertas
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if (can('alertas.config')): ?>
        <a href="<?= url('alertas/config') ?>" class="btn btn-secondary">Ajustes</a>
        <?php endif; ?>
        <?php if ($esMatriz): ?>
        <?php if ($solo_pendientes): ?>
        <a href="<?= e(alerta_index_url(['pendientes' => null])) ?>" class="btn btn-sm btn-info">Ver todos los vehículos</a>
        <?php else: ?>
        <a href="<?= e(alerta_index_url(['pendientes' => 1])) ?>" class="btn btn-sm btn-warning">Solo con avisos</a>
        <?php endif; ?>
        <a href="<?= e(alerta_index_url(['historial' => 1])) ?>" class="btn btn-sm btn-info">Historial</a>
        <?php else: ?>
        <a href="<?= e(alerta_index_url(['historial' => null, 'todas' => null])) ?>" class="btn btn-sm btn-info">Vista por vehículo</a>
        <?php endif; ?>
    </div>
</div>

<?php App\Core\View::component('alertas-guia'); ?>

<?php if (!empty($vehiculos)): ?>
<div class="card alertas-filtro-vehiculo">
    <form class="filters-bar card-body" method="get" action="<?= url('alertas') ?>">
        <?php if ($solo_pendientes): ?>
        <input type="hidden" name="pendientes" value="1">
        <?php endif; ?>
        <?php if (!$esMatriz): ?>
        <input type="hidden" name="historial" value="1">
        <?php endif; ?>
        <div class="form-group">
            <label class="form-label" for="vehiculo_id">Vehículo</label>
            <select id="vehiculo_id" name="vehiculo_id" class="form-select" onchange="this.form.submit()">
                <option value="">Todos los vehículos</option>
                <?php foreach ($vehiculos as $v): ?>
                <option value="<?= (int) $v['id'] ?>" <?= $vehiculo_id === (int) $v['id'] ? 'selected' : '' ?>>
                    <?= e(catalogo_vehiculo_label($v)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($vehiculo_id > 0): ?>
        <a href="<?= e(alerta_index_url(['vehiculo_id' => null])) ?>" class="btn btn-secondary">Quitar filtro</a>
        <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<?php if ($esMatriz && $totalPendientes > 0): ?>
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
        <?php if ($esMatriz && $solo_pendientes): ?>
        <?php if ($vehiculo_id > 0): ?>
        Este vehículo no tiene avisos pendientes en este momento.
        <?php else: ?>
        Ningún vehículo con avisos pendientes en este momento.
        <?php endif; ?>
        <?php elseif ($esMatriz && $vehiculo_id > 0): ?>
        Vehículo no encontrado o no disponible.
        <?php elseif ($esMatriz): ?>
        No hay vehículos registrados para mostrar.
        <?php else: ?>
        No hay alertas en el historial<?= $vehiculo_id > 0 ? ' para este vehículo' : '' ?>.
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
    $kmVehiculo = (int) ($grupo['kilometraje_actual'] ?? 0);
    ?>
    <section class="card alertas-vehiculo<?= $nivelGrupo !== '' ? ' alertas-vehiculo--' . e($nivelGrupo) : '' ?>">
        <div class="alertas-vehiculo-header">
            <div class="alertas-vehiculo-info">
                <h2 class="alertas-vehiculo-nombre"><?= e($grupo['numero_economico']) ?></h2>
                <p class="alertas-vehiculo-meta">
                    <?php if ($esMatriz): ?>
                    <?= number_format($kmVehiculo, 0, '.', ',') ?> km actuales · <?= $totalGrupo ?> servicio<?= $totalGrupo === 1 ? '' : 's' ?>
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
                        <th>Servicio</th>
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
                    $estado = $esMantenimiento ? alerta_estado_mantenimiento($a) : [
                        'label' => alerta_nivel_label($a['nivel'] ?? null),
                        'class' => semaforo_class($a['nivel'] ?? null),
                    ];
                    $proximaVencida = !empty($a['fecha_proximo_mantenimiento'])
                        && $a['fecha_proximo_mantenimiento'] < date('Y-m-d')
                        && !$sinAlta;
                    $accionUrl = alerta_accion_url($a);
                    $resumen = alerta_resumen_fila($a);
                    $puedeRegistrar = $esMantenimiento && can('mantenimiento.create');
                    $puedeAtenderDoc = !$esMantenimiento && can('documentos.read');
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
                            <span class="text-muted">Documento</span>
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
<?php if ($vehiculo_id <= 0): ?>
<?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
<?php endif; ?>
<?php endif; ?>
