<?php
$pageTitle = 'Dashboard';
$kpis = $kpis ?? [];
$proximos_servicios = $proximos_servicios ?? [];
$alertas = $alertas ?? [];
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
        <span class="dash-kpi-label">Alertas críticas</span>
        <span class="dash-kpi-value text-danger"><?= (int) ($kpis['alertas_rojas'] ?? 0) ?></span>
        <span class="dash-kpi-note"><?= (int) ($kpis['alertas_pendientes'] ?? 0) ?> pendientes total</span>
    </div>
    <div class="dash-kpi">
        <span class="dash-kpi-label">Por atender</span>
        <span class="dash-kpi-value text-warning"><?= (int) ($kpis['servicios_pendientes'] ?? 0) + (int) ($kpis['docs_por_vencer'] ?? 0) ?></span>
        <span class="dash-kpi-note">mant. + docs 60 días</span>
    </div>
</div>

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

<div class="dash-layout">
    <div class="dash-main">
        <section class="dash-section dash-section-flush">
            <div class="dash-section-head">
                <div>
                    <h2 class="dash-section-title">Próximo a mantenimiento</h2>
                    <p class="dash-section-desc">Ordenado por urgencia según kilometraje recorrido</p>
                </div>
                <?php if (can('mantenimiento.create')): ?>
                <a href="<?= url('mantenimiento/create') ?>" class="btn btn-sm btn-primary">Programar servicio</a>
                <?php endif; ?>
            </div>
            <div class="card">
                <?php if (empty($proximos_servicios)): ?>
                <div class="card-body">
                    <p class="dash-empty">Ningún vehículo requiere servicio preventivo por ahora.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table dash-table">
                        <thead>
                            <tr>
                                <th>Vehículo</th>
                                <th>Servicio</th>
                                <th>Kilometraje</th>
                                <th>Último servicio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($proximos_servicios, 0, 8) as $item): ?>
                            <tr>
                                <td>
                                    <span class="badge <?= semaforo_class($item['nivel']) ?> dash-priority-dot"><?= e(ucfirst($item['nivel'])) ?></span>
                                    <strong class="dash-cell-title"><?= e($item['numero_economico']) ?></strong>
                                </td>
                                <td><?= e($item['servicio']) ?></td>
                                <td class="dash-km-cell">
                                    <span><?= number_format((int) $item['km_desde_servicio']) ?> km recorridos</span>
                                    <?php if ((int) $item['km_vencido'] > 0): ?>
                                    <small class="text-danger">Vencido por <?= number_format((int) $item['km_vencido']) ?> km</small>
                                    <?php else: ?>
                                    <small class="text-muted">Faltan <?= number_format((int) $item['km_restante']) ?> km</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($item['ultimo_servicio']) ? format_date($item['ultimo_servicio']) : '<span class="text-muted">Sin registro</span>' ?></td>
                                <td class="text-right">
                                    <?php if (can('expediente.read')): ?>
                                    <a href="<?= url('vehiculos/' . (int) $item['vehiculo_id']) ?>" class="btn btn-sm btn-info">Expediente</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <aside class="dash-sidebar">
        <section class="dash-section dash-section-flush">
            <div class="dash-section-head">
                <div>
                    <h2 class="dash-section-title">Requiere atención</h2>
                    <p class="dash-section-desc">Alertas y vencimientos</p>
                </div>
            </div>

            <div class="dash-stack">
                <div class="card">
                    <div class="card-header">
                        <h3>Alertas</h3>
                        <a href="<?= url('alertas') ?>" class="btn btn-sm btn-info">Ver todas</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($alertas)): ?>
                        <p class="dash-empty">Sin alertas pendientes.</p>
                        <?php else: ?>
                        <ul class="dash-feed">
                            <?php foreach (array_slice($alertas, 0, 5) as $a): ?>
                            <li class="dash-feed-item">
                                <?php
                                $alertaHref = !empty($a['vehiculo_id']) && can('expediente.read')
                                    ? url('vehiculos/' . (int) $a['vehiculo_id'])
                                    : url('alertas');
                                ?>
                                <a href="<?= e($alertaHref) ?>" class="dash-feed-link">
                                    <span class="dash-feed-row">
                                        <span class="badge <?= semaforo_class($a['nivel'] ?? null) ?>"><?= e(ucfirst((string) ($a['nivel'] ?? '—'))) ?></span>
                                        <span class="dash-feed-title dash-feed-title-truncate"><?= e($a['titulo']) ?></span>
                                    </span>
                                    <span class="dash-feed-meta"><?= e(mb_substr($a['mensaje'], 0, 100)) ?><?= mb_strlen($a['mensaje']) > 100 ? '…' : '' ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Documentos por vencer</h3>
                        <a href="<?= url('documentos') ?>" class="btn btn-sm btn-info">Ver todos</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($documentos)): ?>
                        <p class="dash-empty">Nada próximo a vencer.</p>
                        <?php else: ?>
                        <ul class="dash-feed">
                            <?php foreach (array_slice($documentos, 0, 5) as $doc): ?>
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
            </div>
        </section>
    </aside>
</div>
