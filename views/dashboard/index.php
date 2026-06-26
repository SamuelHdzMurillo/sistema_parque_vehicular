<?php
$pageTitle = 'Dashboard';
$kpis = $kpis ?? [];
$proximos_servicios = $proximos_servicios ?? [];
$alertas_grupos = $alertas_grupos ?? [];
$alertas_total_grupos = (int) ($alertas_total_grupos ?? 0);
$mantenimientos_por_vencer = $mantenimientos_por_vencer ?? [];
$documentos = $documentos ?? [];
$mantenimientos = $mantenimientos ?? [];
$comisiones = $comisiones ?? [];
$danios = $danios ?? [];

$mantEstados = [
    'pendiente' => 'Pendiente', 'programado' => 'Programado', 'autorizado' => 'Autorizado',
    'en_proceso' => 'En proceso', 'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado',
];
$danioEstados = [
    'reportado' => 'Reportado', 'en_evaluacion' => 'En evaluación',
    'en_reparacion' => 'En reparación', 'reparado' => 'Reparado', 'cerrado_sin_accion' => 'Cerrado',
];
$docTipos = [
    'poliza' => 'Póliza', 'tenencia' => 'Tenencia', 'verificacion' => 'Verificación',
    'licencia' => 'Licencia', 'tarjeta_circulacion' => 'Tarjeta circulación', 'factura' => 'Factura', 'otro' => 'Otro',
];

$alertasRojas = (int) ($kpis['alertas_rojas'] ?? 0);
$alertasAmarillas = (int) ($kpis['alertas_amarillas'] ?? 0);
$alertasVerdes = (int) ($kpis['alertas_verdes'] ?? 0);
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Panel de control</h1>
        <p class="page-subtitle">Qué requiere atención hoy — <?= e((string) config('app', 'institution')) ?></p>
    </div>
    <div class="page-actions">
        <?php if (can('vehiculos.create')): ?>
        <a href="<?= url('vehiculos/create') ?>" class="btn btn-primary">+ Nuevo vehículo</a>
        <?php endif; ?>
        <?php if (can('comisiones.create')): ?>
        <a href="<?= url('comisiones/create') ?>" class="btn btn-accent">+ Nueva comisión</a>
        <?php endif; ?>
    </div>
</div>

<div class="dash-kpi-strip">
    <div class="dash-kpi">
        <span class="dash-kpi-label">Operativos</span>
        <span class="dash-kpi-value text-success"><?= (int) ($kpis['vehiculos_operativos'] ?? 0) ?></span>
        <span class="dash-kpi-note">de <?= (int) ($kpis['vehiculos_total'] ?? 0) ?> vehículos</span>
    </div>
    <div class="dash-kpi">
        <span class="dash-kpi-label">En comisión</span>
        <span class="dash-kpi-value text-info"><?= (int) ($kpis['comisiones_activas'] ?? 0) ?></span>
        <span class="dash-kpi-note">salidas activas</span>
    </div>
    <div class="dash-kpi">
        <span class="dash-kpi-label">Urgentes</span>
        <span class="dash-kpi-value text-danger"><?= $alertasRojas ?></span>
        <span class="dash-kpi-note"><?= $alertasAmarillas ?> en atención · <?= $alertasVerdes ?> avisos</span>
    </div>
    <div class="dash-kpi">
        <span class="dash-kpi-label">Por atender</span>
        <span class="dash-kpi-value text-warning"><?= (int) ($kpis['servicios_pendientes'] ?? 0) + (int) ($kpis['docs_por_vencer'] ?? 0) ?></span>
        <span class="dash-kpi-note">mant. abiertos + docs 60 días</span>
    </div>
</div>

<section class="dash-section dash-section-alerts">
    <div class="dash-section-head">
        <div>
            <h2 class="dash-section-title">Alertas</h2>
            <p class="dash-section-desc">Misma vista que Alertas: mantenimientos y documentos por vencer</p>
        </div>
        <div class="dash-section-actions">
            <?php if ($alertasRojas > 0): ?>
            <span class="alertas-resumen-chip alertas-resumen-chip--rojo"><?= $alertasRojas ?> urgente<?= $alertasRojas === 1 ? '' : 's' ?></span>
            <?php endif; ?>
            <?php if ($alertasAmarillas > 0): ?>
            <span class="alertas-resumen-chip alertas-resumen-chip--amarillo"><?= $alertasAmarillas ?> atención</span>
            <?php endif; ?>
            <?php if ($alertasVerdes > 0): ?>
            <span class="alertas-resumen-chip alertas-resumen-chip--verde"><?= $alertasVerdes ?> aviso<?= $alertasVerdes === 1 ? '' : 's' ?></span>
            <?php endif; ?>
            <a href="<?= url('alertas') ?>" class="btn btn-sm btn-info">Abrir en alertas</a>
        </div>
    </div>

    <?php if (empty($alertas_grupos)): ?>
    <div class="card">
        <div class="empty-state py-5 text-center text-muted">
            Ningún vehículo con avisos pendientes en este momento.
        </div>
    </div>
    <?php else: ?>
    <?php App\Core\View::component('alertas-grupos-list', ['grupos' => $alertas_grupos, 'esMatriz' => true]); ?>
    <?php if ($alertas_total_grupos > count($alertas_grupos)): ?>
    <p class="dash-more-link text-muted text-center mt-2">
        Mostrando <?= count($alertas_grupos) ?> de <?= $alertas_total_grupos ?> vehículos.
        <a href="<?= url('alertas') ?>">Ver todos en alertas</a>
    </p>
    <?php endif; ?>
    <?php endif; ?>
</section>

<?php if (!empty($mantenimientos_por_vencer)): ?>
<section class="dash-section">
    <div class="dash-section-head">
        <div>
            <h2 class="dash-section-title">Mantenimientos por vencer</h2>
            <p class="dash-section-desc">Servicios preventivos próximos según fecha o kilometraje</p>
        </div>
        <a href="<?= url('alertas') ?>" class="btn btn-sm btn-info">Ver en alertas</a>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table dash-table">
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Servicio</th>
                        <th>Estado</th>
                        <th>Último mantenimiento</th>
                        <th>Próximo toca</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($mantenimientos_por_vencer, 0, 12) as $item): ?>
                    <?php
                    $estado = alerta_estado_mantenimiento($item);
                    $proximaVencida = !empty($item['fecha_proximo_mantenimiento'])
                        && $item['fecha_proximo_mantenimiento'] < date('Y-m-d');
                    ?>
                    <tr>
                        <td>
                            <?php if (can('expediente.read')): ?>
                            <a href="<?= url('vehiculos/' . (int) $item['vehiculo_id']) ?>" class="dash-alerts-vehiculo-link">
                                <?= e($item['numero_economico']) ?>
                            </a>
                            <?php else: ?>
                            <strong><?= e($item['numero_economico']) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td><?= e($item['servicio_nombre'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= e($estado['class']) ?>"><?= e($estado['label']) ?></span>
                        </td>
                        <td>
                            <?php if (!empty($item['mantenimiento_id']) && can('mantenimiento.read')): ?>
                            <a href="<?= url('mantenimiento/' . (int) $item['mantenimiento_id']) ?>">
                                <?= e(alerta_ultimo_mantenimiento_display($item)) ?>
                            </a>
                            <?php else: ?>
                            <?= e(alerta_ultimo_mantenimiento_display($item)) ?>
                            <?php endif; ?>
                        </td>
                        <td class="<?= $proximaVencida ? 'text-danger' : '' ?>">
                            <?php $proximo = alerta_proximo_partes($item); ?>
                            <?php if ($proximo['fecha'] !== null): ?>
                            <span><?= e($proximo['fecha']) ?></span>
                            <?php endif; ?>
                            <?php if ($proximo['km'] !== null): ?>
                            <small class="text-muted"><?= e($proximo['km']) ?></small>
                            <?php endif; ?>
                            <?php if ($proximo['fecha'] === null && $proximo['km'] === null): ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">
                            <a href="<?= e(alerta_accion_url($item)) ?>" class="btn btn-sm btn-primary">Atender</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="dash-section">
    <div class="dash-section-head">
        <div>
            <h2 class="dash-section-title">Actividad en curso</h2>
            <p class="dash-section-desc">Comisiones y mantenimientos abiertos en este momento</p>
        </div>
    </div>

    <div class="dash-activity-grid">
        <div class="card">
            <div class="card-header">
                <h3>Comisiones en curso</h3>
                <a href="<?= url('comisiones') ?>" class="btn btn-sm btn-info">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($comisiones)): ?>
                <p class="dash-empty">Ninguna comisión activa.</p>
                <?php else: ?>
                <ul class="dash-feed">
                    <?php foreach (array_slice($comisiones, 0, 5) as $c): ?>
                    <li class="dash-feed-item">
                        <?php if (can('comisiones.read')): ?>
                        <a href="<?= url('comisiones/' . (int) $c['id']) ?>" class="dash-feed-link">
                        <?php endif; ?>
                            <span class="dash-feed-title"><?= e($c['numero_economico']) ?> · <?= e($c['destino']) ?></span>
                            <span class="dash-feed-meta">
                                <?= e($c['conductor_nombre']) ?>
                                · salió <?= format_date($c['fecha']) ?> a las <?= e(substr((string) $c['hora_salida'], 0, 5)) ?>
                            </span>
                        <?php if (can('comisiones.read')): ?>
                        </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Mantenimientos abiertos</h3>
                <a href="<?= url('mantenimiento') ?>" class="btn btn-sm btn-info">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($mantenimientos)): ?>
                <p class="dash-empty">No hay mantenimientos pendientes.</p>
                <?php else: ?>
                <ul class="dash-feed">
                    <?php foreach (array_slice($mantenimientos, 0, 5) as $m): ?>
                    <li class="dash-feed-item">
                        <?php if (can('mantenimiento.read')): ?>
                        <a href="<?= url('mantenimiento/' . (int) $m['id']) ?>" class="dash-feed-link">
                        <?php endif; ?>
                            <span class="dash-feed-row">
                                <span class="dash-feed-title"><?= e($m['numero_economico']) ?> · <?= e($m['folio']) ?></span>
                                <span class="badge badge-secondary"><?= e($mantEstados[$m['estado']] ?? $m['estado']) ?></span>
                            </span>
                            <span class="dash-feed-meta">
                                <?= e(ucfirst($m['tipo'])) ?> · programado <?= format_date($m['fecha']) ?>
                                <?php if (!empty($m['proveedor'])): ?> · <?= e($m['proveedor']) ?><?php endif; ?>
                            </span>
                        <?php if (can('mantenimiento.read')): ?>
                        </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($danios)): ?>
        <div class="card dash-activity-full">
            <div class="card-header">
                <h3>Daños sin resolver</h3>
                <a href="<?= url('danios') ?>" class="btn btn-sm btn-info">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <ul class="dash-feed dash-feed-cols">
                    <?php foreach ($danios as $d): ?>
                    <li class="dash-feed-item">
                        <?php if (can('danios.read')): ?>
                        <a href="<?= url('danios/' . (int) $d['id']) ?>" class="dash-feed-link">
                        <?php endif; ?>
                            <span class="dash-feed-row">
                                <span class="dash-feed-title"><?= e($d['numero_economico']) ?> · <?= e(ucfirst(str_replace('_', ' ', $d['tipo_dano']))) ?></span>
                                <span class="badge badge-warning"><?= e($danioEstados[$d['estado']] ?? $d['estado']) ?></span>
                            </span>
                            <span class="dash-feed-meta"><?= e($d['ubicacion']) ?> · reportado <?= format_date($d['created_at']) ?></span>
                        <?php if (can('danios.read')): ?>
                        </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="dash-section">
    <div class="dash-section-head">
        <div>
            <h2 class="dash-section-title">Documentos por vencer</h2>
            <p class="dash-section-desc">Vencimientos en los próximos 60 días</p>
        </div>
        <a href="<?= url('documentos') ?>" class="btn btn-sm btn-info">Ver todos</a>
    </div>
    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($documentos)): ?>
            <p class="dash-empty">Nada próximo a vencer.</p>
            <?php else: ?>
            <ul class="dash-feed">
                <?php foreach (array_slice($documentos, 0, 6) as $doc): ?>
                <?php
                $dias = (int) ($doc['dias_restantes'] ?? 0);
                $nivelDoc = $dias < 0 ? 'rojo' : ($dias <= 30 ? 'amarillo' : 'verde');
                ?>
                <li class="dash-feed-item">
                    <?php if (can('expediente.read')): ?>
                    <a href="<?= url('vehiculos/' . (int) $doc['vehiculo_id']) ?>" class="dash-feed-link">
                    <?php endif; ?>
                        <span class="dash-feed-row">
                            <span class="badge <?= semaforo_class($nivelDoc) ?>"><?= $dias < 0 ? 'Vencido' : $dias . ' días' ?></span>
                            <span class="dash-feed-title dash-feed-title-truncate"><?= e($doc['numero_economico']) ?></span>
                        </span>
                        <span class="dash-feed-meta">
                            <?= e($doc['titulo']) ?> · <?= e($docTipos[$doc['tipo']] ?? ucfirst($doc['tipo'])) ?>
                            · vence <?= format_date($doc['fecha_vencimiento']) ?>
                        </span>
                    <?php if (can('expediente.read')): ?>
                    </a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</section>
