<?php
$pageTitle = 'Mantenimiento ' . ($mantenimiento['folio'] ?? '');
$m = $mantenimiento ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('mantenimiento') ?>">Mantenimiento</a></li><li>/ <?= e($m['folio'] ?? '') ?></li></ul>
        <h1 class="page-title"><?= e($m['folio'] ?? '') ?></h1>
        <p class="page-subtitle">
            <span class="badge badge-secondary"><?= e(str_replace('_', ' ', $m['estado'] ?? '')) ?></span>
            <?php if (!empty($m['es_historico'])): ?>
            <span class="badge badge-info">Registro histórico</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="page-actions">
        <?php
        $pdfCacheV = (string) ($m['id'] ?? '0');
        if (!empty($m['factura_ruta'])) {
            $facturaFile = storage_path('uploads/' . ltrim((string) $m['factura_ruta'], '/'));
            if (is_file($facturaFile)) {
                $pdfCacheV .= '_' . (string) filemtime($facturaFile);
            }
        } else {
            $pdfCacheV .= '_' . (string) strtotime((string) ($m['updated_at'] ?? $m['created_at'] ?? 'now'));
        }
        ?>
        <a href="<?= url('formatos/mantenimiento/' . $m['id']) ?>?v=<?= e($pdfCacheV) ?>" class="btn btn-secondary" target="_blank">Descargar PDF / Imprimir</a>
        <?php if (can('mantenimiento.update')): ?>
        <a href="<?= url('mantenimiento/' . $m['id'] . '/edit') ?>" class="btn btn-secondary">Editar</a>
        <?php endif; ?>
        <?php if (can('mantenimiento.delete')): ?>
        <form action="<?= url('mantenimiento/' . $m['id'] . '/eliminar') ?>" method="post" class="inline-form">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-danger" data-confirm="¿Confirma eliminar este mantenimiento (<?= e($m['folio'] ?? '') ?>)? Esta acción no se puede deshacer y el folio quedará disponible para reutilizarse.">Eliminar</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <div class="meta-grid">
            <div class="meta-item"><label>Folio de servicio</label><span><strong><?= e($m['folio'] ?? '—') ?></strong></span></div>
            <div class="meta-item"><label>Vehículo</label><span><?= e($m['numero_economico'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Tipo</label><span><?= e(ucfirst($m['tipo'] ?? '')) ?></span></div>
            <?php
            $serviciosM = $m['servicios'] ?? (!empty($m['servicio']) ? [(string) $m['servicio']] : []);
            if (!empty($serviciosM)): ?>
            <div class="meta-item"><label>Servicios</label><span><?= e(mantenimiento_servicios_labels($serviciosM)) ?></span></div>
            <?php endif; ?>
            <?php if (($m['tipo'] ?? '') === 'preventivo' && !empty($m['servicios_intervalos'])): ?>
            <div class="meta-item meta-item--full"><label>Próximo servicio programado</label>
                <ul class="mb-0 pl-3">
                    <?php foreach ($m['servicios_intervalos'] as $si):
                        $partesProximo = mantenimiento_proximo_servicio_partes($m, $si);
                    ?>
                    <li>
                        <strong><?= e(mantenimiento_servicio_label((string) ($si['servicio'] ?? ''))) ?></strong>
                        <?php if ($partesProximo !== []): ?>
                        <ul class="mb-0 pl-3">
                            <?php foreach ($partesProximo as $parte): ?>
                            <li><?= e($parte) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <span class="text-muted"> —</span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <div class="meta-item"><label>Fecha</label><span><?= format_date($m['fecha'] ?? null) ?></span></div>
            <div class="meta-item"><label>Kilometraje</label><span><?= number_format((int) ($m['kilometraje'] ?? 0)) ?></span></div>
            <div class="meta-item"><label>Proveedor</label><span><?= e($m['proveedor_nombre'] ?? $m['razon_social'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Costo</label><span><?= format_money($m['costo'] ?? 0) ?></span></div>
            <div class="meta-item"><label>Responsable</label><span><?= e($m['responsable_nombre'] ?? '—') ?></span></div>
        </div>
        <p class="mt-2"><strong>Descripción:</strong> <?= e($m['descripcion'] ?? '') ?></p>
        <?php if (!empty($m['observaciones'])): ?>
        <p><strong>Observaciones:</strong> <?= e($m['observaciones']) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($m['proveedor_id'])): ?>
<div class="card mb-2">
    <div class="card-header"><h3>Datos del proveedor</h3></div>
    <div class="card-body">
        <div class="meta-grid">
            <div class="meta-item"><label>Razón social</label><span><?= e($m['proveedor_nombre'] ?? '—') ?></span></div>
            <div class="meta-item"><label>RFC</label><span><?= e($m['proveedor_rfc'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Teléfono</label><span><?= e($m['proveedor_telefono'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Email</label><span><?= e($m['proveedor_email'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Dirección</label><span><?= e($m['proveedor_direccion'] ?? '—') ?></span></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$tieneFactura = !empty($m['factura_folio']) || !empty($m['factura_uuid']) || !empty($m['factura_total'])
    || !empty($m['factura_ruta']) || !empty($m['xml_ruta']);
?>
<?php if ($tieneFactura): ?>
<div class="card mb-2">
    <div class="card-header"><h3>Factura</h3></div>
    <div class="card-body">
        <div class="meta-grid">
            <div class="meta-item"><label>Folio / serie</label><span><?= e($m['factura_folio'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Fecha</label><span><?= !empty($m['factura_fecha']) ? format_date($m['factura_fecha']) : '—' ?></span></div>
            <div class="meta-item"><label>Folio fiscal (UUID)</label><span><?= e($m['factura_uuid'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Subtotal</label><span><?= isset($m['factura_subtotal']) && $m['factura_subtotal'] !== null ? format_money($m['factura_subtotal']) : '—' ?></span></div>
            <div class="meta-item"><label>IVA</label><span><?= isset($m['factura_iva']) && $m['factura_iva'] !== null ? format_money($m['factura_iva']) : '—' ?></span></div>
            <div class="meta-item"><label>Total</label><span><?= isset($m['factura_total']) && $m['factura_total'] !== null ? format_money($m['factura_total']) : '—' ?></span></div>
        </div>
        <?php
        $facturaUrl = !empty($m['factura_ruta']) ? url('storage/uploads/' . ltrim((string) $m['factura_ruta'], '/')) : '';
        $facturaExt = !empty($m['factura_ruta']) ? strtolower((string) pathinfo((string) $m['factura_ruta'], PATHINFO_EXTENSION)) : '';
        $facturaEsImagen = in_array($facturaExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
        ?>
        <?php if ($facturaUrl !== '' && $facturaEsImagen): ?>
        <div class="mt-2">
            <a href="<?= e($facturaUrl) ?>" target="_blank">
                <img src="<?= e($facturaUrl) ?>" alt="Factura" style="max-width:100%;max-height:480px;border:1px solid var(--border-color);border-radius:var(--radius);">
            </a>
        </div>
        <?php endif; ?>
        <?php if (!empty($m['factura_ruta']) || !empty($m['xml_ruta'])): ?>
        <div class="d-flex gap-1 mt-2">
            <?php if (!empty($m['factura_ruta'])): ?>
            <a href="<?= e($facturaUrl) ?>" class="btn btn-sm btn-secondary" target="_blank"><?= $facturaEsImagen ? 'Ver factura en grande' : 'Ver archivo de factura' ?></a>
            <?php endif; ?>
            <?php if (!empty($m['xml_ruta'])): ?>
            <a href="<?= url('storage/uploads/' . ltrim((string) $m['xml_ruta'], '/')) ?>" class="btn btn-sm btn-secondary" target="_blank">Descargar XML (CFDI)</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (($m['estado'] ?? '') === 'pendiente' && can('mantenimiento.authorize')): ?>
<div class="card mb-2">
    <div class="card-header"><h3>Autorizar servicio</h3></div>
    <div class="card-body">
        <form action="<?= url('mantenimiento/' . $m['id'] . '/autorizar') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-accent">Autorizar mantenimiento</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (in_array($m['estado'] ?? '', ['autorizado', 'en_proceso'], true) && can('mantenimiento.update')): ?>
<div class="card">
    <div class="card-header"><h3>Finalizar servicio</h3></div>
    <div class="card-body">
        <form action="<?= url('mantenimiento/' . $m['id'] . '/finalizar') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="costo_final">Costo final</label>
                    <input type="number" id="costo_final" name="costo" class="form-control" step="0.01" value="<?= e((string) ($m['costo'] ?? '0')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="observaciones_fin">Observaciones</label>
                    <input type="text" id="observaciones_fin" name="observaciones" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Marcar como finalizado</button>
        </form>
    </div>
</div>
<?php endif; ?>
