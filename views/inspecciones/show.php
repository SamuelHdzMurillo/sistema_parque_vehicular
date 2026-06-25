<?php
$pageTitle = 'Inspección';
$inspeccion = $inspeccion ?? [];
$i = $inspeccion;
$calLabels = ['bueno' => 'Bueno', 'regular' => 'Regular', 'malo' => 'Malo'];
$calBadge = ['bueno' => 'badge-success', 'regular' => 'badge-warning', 'malo' => 'badge-danger'];
$lucesCatalog = \App\Repositories\InspeccionRepository::LUCES_TABLERO;
$lucesOn = array_column($i['luces_tablero'] ?? [], 'luz_codigo');
$daniosAbiertos = $danios_abiertos ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('inspecciones') ?>">Inspecciones</a></li><li>/ Detalle</li></ul>
        <h1 class="page-title">Inspección — <?= e(inspeccion_folio($i)) ?></h1>
        <p class="page-subtitle">
            Fecha de inspección: <?= format_date($i['fecha']) ?> ·
            <span class="badge <?= ($i['resultado_general'] ?? '') === 'aprobada' ? 'badge-success' : (($i['resultado_general'] ?? '') === 'rechazada' ? 'badge-danger' : 'badge-warning') ?>">
                <?= e(ucfirst($i['resultado_general'] ?? '')) ?>
            </span>
            <?php if (!empty($i['es_historico'])): ?>
            <span class="badge badge-info">Registro histórico</span>
            <?php endif; ?>
        </p>
    </div>
    <?php if (!empty($i['vehiculo_id']) || can('inspecciones.delete')): ?>
    <div class="page-actions">
        <?php if (!empty($i['vehiculo_id'])): ?>
        <a href="<?= url('formatos/inspeccion/' . $i['id']) ?>" class="btn btn-secondary" target="_blank">Descargar PDF / Imprimir</a>
        <a href="<?= url('vehiculos/' . $i['vehiculo_id']) ?>" class="btn btn-secondary">Ver expediente</a>
        <?php endif; ?>
        <?php if (can('inspecciones.delete')): ?>
        <form action="<?= url('inspecciones/' . $i['id'] . '/eliminar') ?>" method="post" class="inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger" data-confirm="¿Confirma eliminar esta inspección? Esta acción no se puede deshacer.">Eliminar</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="card mb-2">
    <div class="card-body">
        <div class="meta-grid">
            <div class="meta-item"><label>Folio</label><span><?= e(inspeccion_folio($i)) ?></span></div>
            <div class="meta-item"><label>Vehículo</label><span><?= e($i['numero_economico'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Responsable</label><span><?= e($i['responsable_nombre'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Fecha de inspección</label><span><?= format_date($i['fecha']) ?></span></div>
            <div class="meta-item"><label>Kilometraje</label><span><?= number_format((int) ($i['kilometraje'] ?? 0)) ?> km</span></div>
            <div class="meta-item"><label>Combustible (gasolina)</label><span><?= e(combustible_fraccion_etiqueta($i['nivel_combustible'] ?? null)) ?></span></div>
            <div class="meta-item"><label>Fecha de registro en sistema</label><span><?= format_datetime($i['created_at'] ?? null) ?></span></div>
        </div>
        <?php if (!empty($i['observaciones_generales'])): ?>
        <p class="mt-2"><strong>Observaciones generales:</strong> <?= e($i['observaciones_generales']) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($daniosAbiertos !== []): ?>
<div class="card mb-2">
    <div class="card-header"><h3>Daños no resueltos al momento de la inspección</h3></div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>No.</th><th>Tipo</th><th>Ubicación</th><th>Estado</th><th>Fecha reporte</th><th>Descripción</th></tr></thead>
            <tbody>
                <?php foreach ($daniosAbiertos as $d): ?>
                <tr>
                    <td><a href="<?= url('danios/' . $d['id']) ?>">#<?= (int) $d['id'] ?></a></td>
                    <td><?= e(ucfirst($d['tipo_dano'] ?? '')) ?></td>
                    <td><?= e($d['ubicacion'] ?? '—') ?></td>
                    <td><span class="badge badge-warning"><?= e(str_replace('_', ' ', $d['estado'] ?? '')) ?></span></td>
                    <td><?= format_datetime($d['created_at'] ?? null) ?></td>
                    <td><?= e($d['descripcion'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card mb-2">
    <div class="card-header"><h3>Resultados del checklist</h3></div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Ítem</th><th>Calificación</th><th>Observaciones</th></tr></thead>
            <tbody>
                <?php foreach ($i['items'] ?? [] as $item): ?>
                <tr>
                    <td><?= e($item['item_nombre']) ?></td>
                    <td><span class="badge <?= $calBadge[$item['calificacion']] ?? 'badge-secondary' ?>"><?= e($calLabels[$item['calificacion']] ?? $item['calificacion']) ?></span></td>
                    <td><?= e($item['observaciones'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($lucesCatalog)): ?>
<div class="card mb-2">
    <div class="card-header">
        <h3>Luces del tablero</h3>
        <?php if (empty($lucesOn)): ?>
        <p class="card-header-hint">Ninguna luz de advertencia reportada encendida.</p>
        <?php else: ?>
        <p class="card-header-hint"><?= count($lucesOn) ?> luz(es) encendida(s) al momento de la inspección.</p>
        <?php endif; ?>
    </div>
    <div class="card-body">
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
    </div>
</div>
<?php endif; ?>

<?php if (!empty($i['firma_digital'])): ?>
<div class="card">
    <div class="card-header"><h3>Firma digital</h3></div>
    <div class="card-body">
        <img src="<?= e(url('storage/uploads/' . ltrim($i['firma_digital'], '/'))) ?>" alt="Firma" style="max-width:320px;border:1px solid var(--border-color);border-radius:8px">
    </div>
</div>
<?php endif; ?>
