<?php
$pageTitle = 'Expediente — ' . ($numero_economico ?? '');
$estadosLabel = [
    'activo' => 'Activo', 'disponible' => 'Disponible', 'en_comision' => 'En comisión',
    'en_mantenimiento' => 'En mantenimiento', 'en_taller' => 'En taller',
    'fuera_servicio' => 'Fuera de servicio', 'baja' => 'Baja',
];
$comEstados = ['borrador' => 'Borrador', 'en_curso' => 'En curso', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
$fotoRuta = $foto_principal ?? null;
if (empty($fotoRuta) && !empty($fotos)) {
    foreach ($fotos as $f) {
        if (!empty($f['es_principal'])) {
            $fotoRuta = $f['ruta'];
            break;
        }
    }
    if (empty($fotoRuta)) {
        $fotoRuta = $fotos[0]['ruta'] ?? null;
    }
}
$fotoUrl = !empty($fotoRuta) ? url('storage/uploads/' . ltrim((string) $fotoRuta, '/')) : null;
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('vehiculos') ?>">Vehículos</a></li>
            <li>/ <?= e($numero_economico) ?></li>
        </ul>
    </div>
    <div class="page-actions">
        <?php if (can('vehiculos.update')): ?>
        <a href="<?= url('vehiculos/' . $id . '/edit') ?>" class="btn btn-accent">Editar</a>
        <?php endif; ?>
        <?php if (can('herramientas.read')): ?>
        <a href="<?= url('herramientas/vehiculo/' . $id) ?>" class="btn btn-secondary">Herramientas</a>
        <?php endif; ?>
        <?php if (can('comisiones.create')): ?>
        <a href="<?= url('comisiones/create') ?>?vehiculo_id=<?= (int) $id ?>" class="btn btn-accent">Nueva comisión</a>
        <?php endif; ?>
    </div>
</div>

<div class="expediente-header">
    <?php if ($fotoUrl): ?>
    <img src="<?= e($fotoUrl) ?>" alt="Foto del vehículo" class="expediente-photo">
    <?php else: ?>
    <div class="expediente-photo d-flex align-center justify-between" style="justify-content:center;color:var(--text-muted)">Sin foto</div>
    <?php endif; ?>
    <div class="expediente-meta">
        <h1><?= e($numero_economico) ?> — <?= e($marca . ' ' . $modelo) ?></h1>
        <span class="badge <?= vehiculo_estado_badge($estado) ?>"><?= e($estadosLabel[$estado] ?? $estado) ?></span>
        <div class="meta-grid">
            <div class="meta-item"><label><?= e(vehiculo_identificador_label()) ?></label><span><?= e($numero_economico) ?></span></div>
            <div class="meta-item"><label>Placas</label><span><?= e($placas) ?></span></div>
            <div class="meta-item"><label>VIN</label><span><?= e($serie_vin) ?></span></div>
            <div class="meta-item"><label>Área</label><span><?= e($area_nombre ?? '—') ?></span></div>
            <div class="meta-item"><label>Responsable</label><span><?= e($responsable_nombre ?? '—') ?></span></div>
            <div class="meta-item"><label>Kilometraje</label><span><?= number_format((int) $kilometraje_actual) ?> km</span></div>
            <div class="meta-item"><label>Combustible</label><span><?= e(ucfirst($tipo_combustible)) ?></span></div>
        </div>
    </div>
    <div class="kpi-grid" style="flex:1;min-width:200px;margin:0">
        <div class="kpi-card"><div class="kpi-label">Costo total</div><div class="kpi-value" style="font-size:1.2rem"><?= format_money($kpis['costo_total'] ?? 0) ?></div></div>
        <div class="kpi-card"><div class="kpi-label">Costo / km</div><div class="kpi-value" style="font-size:1.2rem"><?= format_money($kpis['costo_por_km'] ?? 0) ?></div></div>
        <div class="kpi-card"><div class="kpi-label">Rendimiento prom.</div><div class="kpi-value" style="font-size:1.2rem"><?= number_format((float) ($kpis['rendimiento_promedio'] ?? 0), 2) ?> km/L</div></div>
        <div class="kpi-card danger"><div class="kpi-label">Incidencias activas</div><div class="kpi-value"><?= (int) ($kpis['incidencias_activas'] ?? 0) ?></div></div>
    </div>
</div>

<div class="card">
    <div class="card-body" data-tabs>
        <div class="tabs">
            <button type="button" class="tab-btn active" data-tab="general">General</button>
            <button type="button" class="tab-btn" data-tab="comisiones">Comisiones</button>
            <button type="button" class="tab-btn" data-tab="mantenimiento">Mantenimiento</button>
            <button type="button" class="tab-btn" data-tab="combustible">Combustible</button>
            <button type="button" class="tab-btn" data-tab="danios">Daños</button>
            <button type="button" class="tab-btn" data-tab="inspecciones">Inspecciones</button>
            <button type="button" class="tab-btn" data-tab="documentos">Documentos</button>
            <button type="button" class="tab-btn" data-tab="alertas">Alertas</button>
            <button type="button" class="tab-btn" data-tab="costos">Costos</button>
        </div>

        <!-- General -->
        <div id="tab-general" class="tab-panel active">
            <div class="form-row">
                <div class="meta-item"><label>Año</label><span><?= e((string) $anio) ?></span></div>
                <div class="meta-item"><label>Color</label><span><?= e($color) ?></span></div>
                <div class="meta-item"><label>Versión</label><span><?= e($version ?? '—') ?></span></div>
                <div class="meta-item"><label>Motor</label><span><?= e($motor ?? '—') ?></span></div>
                <div class="meta-item"><label>Cap. tanque</label><span><?= e((string) $capacidad_tanque) ?> L</span></div>
                <div class="meta-item"><label>Adquisición</label><span><?= format_date($fecha_adquisicion) ?></span></div>
            </div>
            <?php if (!empty($observaciones)): ?>
            <p class="mt-2"><strong>Observaciones:</strong> <?= e($observaciones) ?></p>
            <?php endif; ?>

            <?php
            $lucesCatalog = \App\Repositories\InspeccionRepository::LUCES_TABLERO;
            $lucesOn = $luces_tablero ?? [];
            $lucesMeta = $luces_tablero_meta ?? null;
            ?>
            <h4 class="mt-3">Estado del tablero</h4>
            <?php if (!empty($lucesMeta['origen_label'])): ?>
            <p class="form-hint text-muted mb-2">
                Última actualización: <?= e($lucesMeta['origen_label']) ?>
                <?php if (!empty($lucesMeta['updated_at'])): ?>
                · <?= format_datetime($lucesMeta['updated_at']) ?>
                <?php endif; ?>
            </p>
            <?php elseif ($lucesOn === []): ?>
            <p class="text-muted">Sin luces de advertencia registradas encendidas.</p>
            <?php endif; ?>
            <div class="dash-lights-grid dash-lights-grid--readonly">
                <?php foreach ($lucesCatalog as $luz): ?>
                <?php $isOn = in_array($luz['codigo'], $lucesOn, true); ?>
                <div class="dash-light-card<?= $isOn ? ' is-on' : ' is-off' ?>">
                    <span class="dash-light-icon" aria-hidden="true">
                        <img src="<?= e(asset('images/luces-tablero/' . $luz['icon'])) ?>" alt="" width="48" height="48">
                    </span>
                    <span class="dash-light-name"><?= e($luz['nombre']) ?></span>
                    <span class="dash-light-status"><?= $isOn ? 'Encendida' : 'Apagada' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($lucesOn !== []): ?>
            <p class="dash-lights-summary mt-2 text-muted">
                <?= count($lucesOn) ?> luz(es) encendida(s) según el último registro de comisión o inspección.
            </p>
            <?php endif; ?>

            <h4 class="mt-3">Fotografías</h4>

            <?php if (can('vehiculos.update')): ?>
            <form action="<?= url('vehiculos/' . $id . '/foto') ?>" method="post" enctype="multipart/form-data" class="vehiculo-fotos-upload">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="fotos">Subir fotografías</label>
                    <input type="file" id="fotos" name="fotos[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required>
                    <p class="form-hint">Seleccione todas las imágenes de una vez (Ctrl o Shift para elegir varias). Luego podrá borrar las que no necesite o elegir la principal.</p>
                </div>
                <button type="submit" class="btn btn-secondary">Subir fotografías</button>
            </form>
            <?php endif; ?>

            <?php if (!empty($fotos)): ?>
            <div class="vehiculo-fotos-grid mt-2">
                <?php foreach ($fotos as $f): ?>
                <?php
                $esPrincipal = !empty($f['es_principal']) || (($foto_principal ?? '') !== '' && ($foto_principal ?? '') === ($f['ruta'] ?? ''));
                $fUrl = url('storage/uploads/' . ltrim((string) $f['ruta'], '/'));
                ?>
                <div class="vehiculo-foto-card<?= $esPrincipal ? ' principal' : '' ?>">
                    <img src="<?= e($fUrl) ?>" alt="<?= e($f['descripcion'] ?? 'Foto del vehículo') ?>">
                    <?php if ($esPrincipal): ?>
                    <span class="vehiculo-foto-badge">Principal</span>
                    <?php endif; ?>
                    <?php if (can('vehiculos.update')): ?>
                    <form action="<?= url('vehiculos/' . $id . '/foto/' . $f['id'] . '/delete') ?>" method="post" class="vehiculo-foto-delete"
                          onsubmit="return confirm('¿Eliminar esta fotografía?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">×</button>
                    </form>
                    <?php endif; ?>
                    <div class="vehiculo-foto-meta">
                        <?php if (can('vehiculos.update') && !$esPrincipal): ?>
                        <form action="<?= url('vehiculos/' . $id . '/foto/' . $f['id'] . '/principal') ?>" method="post">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-secondary">Hacer principal</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted mt-2">Sin fotografías registradas.</p>
            <?php endif; ?>

            <?php if (!empty($estado_historial)): ?>
            <h4 class="mt-3">Historial de estados</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Anterior</th><th>Nuevo</th><th>Usuario</th><th>Motivo</th></tr></thead>
                    <tbody>
                    <?php foreach ($estado_historial as $h): ?>
                    <tr>
                        <td><?= format_datetime($h['created_at']) ?></td>
                        <td><?= e($estadosLabel[$h['estado_anterior']] ?? $h['estado_anterior']) ?></td>
                        <td><?= e($estadosLabel[$h['estado_nuevo']] ?? $h['estado_nuevo']) ?></td>
                        <td><?= e($h['usuario_nombre'] ?? '—') ?></td>
                        <td><?= e($h['motivo'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Comisiones -->
        <div id="tab-comisiones" class="tab-panel">
            <div class="d-flex justify-between align-center mb-2">
                <h4 class="mb-0">Comisiones recientes</h4>
                <?php if (can('comisiones.create')): ?>
                <a href="<?= url('comisiones/create') ?>?vehiculo_id=<?= (int) $id ?>" class="btn btn-sm btn-primary">+ Nueva</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Folio</th><th>Fecha</th><th>Destino</th><th>Km</th><th>Rendimiento</th><th>Estado</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($comisiones)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Sin comisiones</td></tr>
                    <?php else: foreach ($comisiones as $c): ?>
                    <tr>
                        <td><?= e($c['folio']) ?></td>
                        <td><?= format_date($c['fecha']) ?></td>
                        <td><?= e($c['destino']) ?></td>
                        <td><?= $c['km_recorridos'] !== null ? number_format((int) $c['km_recorridos']) : '—' ?></td>
                        <td><?= $c['rendimiento'] !== null ? number_format((float) $c['rendimiento'], 2) . ' km/L' : '—' ?></td>
                        <td><span class="badge badge-secondary"><?= e($comEstados[$c['estado']] ?? $c['estado']) ?></span></td>
                        <td><a href="<?= url('comisiones/' . $c['id']) ?>" class="btn btn-sm btn-info">Ver</a></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mantenimiento -->
        <div id="tab-mantenimiento" class="tab-panel">
            <?php if (can('mantenimiento.create')): ?>
            <a href="<?= url('mantenimiento/create') ?>?vehiculo_id=<?= (int) $id ?>" class="btn btn-sm btn-primary mb-2">+ Registrar mantenimiento</a>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Folio</th><th>Tipo</th><th>Fecha</th><th>Km</th><th>Proveedor</th><th>Costo</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php if (empty($mantenimientos)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Sin registros</td></tr>
                    <?php else: foreach ($mantenimientos as $m): ?>
                    <tr>
                        <td><a href="<?= url('mantenimiento/' . $m['id']) ?>"><?= e($m['folio']) ?></a></td>
                        <td><?= e(ucfirst($m['tipo'])) ?></td>
                        <td><?= format_date($m['fecha']) ?></td>
                        <td><?= number_format((int) $m['kilometraje']) ?></td>
                        <td><?= e($m['proveedor'] ?? '—') ?></td>
                        <td><?= format_money($m['costo']) ?></td>
                        <td><span class="badge badge-secondary"><?= e(str_replace('_', ' ', $m['estado'])) ?></span></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Combustible -->
        <div id="tab-combustible" class="tab-panel">
            <?php if (can('combustible.create')): ?>
            <a href="<?= url('combustible/create') ?>?vehiculo_id=<?= (int) $id ?>" class="btn btn-sm btn-primary mb-2">+ Registrar carga</a>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Litros</th><th>Importe</th><th>Km</th><th>Rendimiento</th><th>Costo/km</th></tr></thead>
                    <tbody>
                    <?php if (empty($combustible)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Sin cargas</td></tr>
                    <?php else: foreach ($combustible as $cb): ?>
                    <tr>
                        <td><?= format_date($cb['fecha']) ?></td>
                        <td><?= number_format((float) $cb['litros'], 2) ?> L</td>
                        <td><?= format_money($cb['importe']) ?></td>
                        <td><?= number_format((int) $cb['kilometraje']) ?></td>
                        <td><?= $cb['rendimiento'] !== null ? number_format((float) $cb['rendimiento'], 2) . ' km/L' : '—' ?></td>
                        <td><?= $cb['costo_por_km'] !== null ? format_money($cb['costo_por_km']) : '—' ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Daños -->
        <div id="tab-danios" class="tab-panel">
            <?php if (can('danios.create')): ?>
            <a href="<?= url('danios/create') ?>?vehiculo_id=<?= (int) $id ?>" class="btn btn-sm btn-primary mb-2">+ Reportar daño</a>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Ubicación</th><th>Descripción</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php if (empty($danios)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Sin daños reportados</td></tr>
                    <?php else: foreach ($danios as $d): ?>
                    <tr>
                        <td><?= format_datetime($d['created_at']) ?></td>
                        <td><?= e($d['tipo_dano']) ?></td>
                        <td><?= e($d['ubicacion']) ?></td>
                        <td><?= e(mb_substr($d['descripcion'], 0, 60)) ?><?= mb_strlen($d['descripcion']) > 60 ? '…' : '' ?></td>
                        <td><a href="<?= url('danios/' . $d['id']) ?>"><span class="badge badge-warning"><?= e(str_replace('_', ' ', $d['estado'])) ?></span></a></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inspecciones -->
        <div id="tab-inspecciones" class="tab-panel">
            <?php if (can('inspecciones.create')): ?>
            <a href="<?= url('inspecciones/create') ?>?vehiculo_id=<?= (int) $id ?>" class="btn btn-sm btn-primary mb-2">+ Nueva inspección</a>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Km</th><th>Resultado</th><th>Ítems malos</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($inspecciones)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Sin inspecciones</td></tr>
                    <?php else: foreach ($inspecciones as $i): ?>
                    <tr>
                        <td><?= format_date($i['fecha']) ?></td>
                        <td><?= number_format((int) $i['kilometraje']) ?></td>
                        <td><span class="badge <?= $i['resultado_general'] === 'aprobada' ? 'badge-success' : ($i['resultado_general'] === 'rechazada' ? 'badge-danger' : 'badge-warning') ?>"><?= e(ucfirst($i['resultado_general'])) ?></span></td>
                        <td><?= (int) ($i['items_malo'] ?? 0) ?></td>
                        <td><a href="<?= url('inspecciones/' . $i['id']) ?>" class="btn btn-sm btn-info">Ver</a></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Documentos -->
        <div id="tab-documentos" class="tab-panel">
            <?php if (can('documentos.create')): ?>
            <a href="<?= url('documentos/create') ?>?vehiculo_id=<?= (int) $id ?>" class="btn btn-sm btn-primary mb-2">+ Subir documento</a>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Tipo</th><th>Título</th><th>No. documento</th><th>Vencimiento</th><th>Versión</th></tr></thead>
                    <tbody>
                    <?php if (empty($documentos)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Sin documentos</td></tr>
                    <?php else: foreach ($documentos as $doc): ?>
                    <tr>
                        <td><?= e(ucfirst(str_replace('_', ' ', $doc['tipo']))) ?></td>
                        <td><?= e($doc['titulo']) ?></td>
                        <td><?= e($doc['numero_documento'] ?? '—') ?></td>
                        <td><?= format_date($doc['fecha_vencimiento']) ?></td>
                        <td><?= e((string) ($doc['version'] ?? '1')) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Alertas -->
        <div id="tab-alertas" class="tab-panel">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Nivel</th><th>Tipo</th><th>Título</th><th>Mensaje</th><th>Fecha</th></tr></thead>
                    <tbody>
                    <?php if (empty($alertas_activas)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Sin alertas activas</td></tr>
                    <?php else: foreach ($alertas_activas as $al): ?>
                    <tr>
                        <td><span class="badge <?= semaforo_class($al['nivel']) ?>"><?= e(ucfirst($al['nivel'])) ?></span></td>
                        <td><?= e($al['tipo']) ?></td>
                        <td><?= e($al['titulo']) ?></td>
                        <td><?= e($al['mensaje']) ?></td>
                        <td><?= format_datetime($al['created_at']) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Costos -->
        <div id="tab-costos" class="tab-panel">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Mantenimiento</div>
                    <div class="kpi-value" style="font-size:1.35rem"><?= format_money($costos['costo_mantenimiento'] ?? 0) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Combustible</div>
                    <div class="kpi-value" style="font-size:1.35rem"><?= format_money($costos['costo_combustible'] ?? 0) ?></div>
                </div>
                <div class="kpi-card primary">
                    <div class="kpi-label">Total acumulado</div>
                    <div class="kpi-value" style="font-size:1.35rem"><?= format_money($costos['costo_total'] ?? 0) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Costo por kilómetro</div>
                    <div class="kpi-value" style="font-size:1.35rem"><?= format_money($kpis['costo_por_km'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
