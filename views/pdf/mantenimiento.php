<?php
require_once view_path('pdf/helpers.php');

$m = $mantenimiento ?? null;
$pdfTitle = 'Orden de Servicio — Mantenimiento';
$pdfSubtitle = $m ? ('Folio: ' . ($m['folio'] ?? '')) : 'Formato en blanco';

$facturaRuta = isset($m['factura_ruta']) ? (string) $m['factura_ruta'] : '';
$facturaEmbed = pdf_prepare_factura($facturaRuta !== '' ? $facturaRuta : null);
$facturaEsPdf = pdf_factura_is_pdf($facturaRuta !== '' ? $facturaRuta : null);

ob_start();
?>
<div class="section">
    <div class="section-title">Datos del servicio</div>
    <?php
    pdf_render_fields([
        ['label' => 'Folio', 'value' => pdf_val($m['folio'] ?? null, '')],
        ['label' => 'Estado', 'value' => pdf_val(isset($m['estado']) ? ucfirst(str_replace('_', ' ', $m['estado'])) : null, '')],
        ['label' => 'Vehículo', 'value' => pdf_val($m['numero_economico'] ?? null, '')],
        ['label' => 'Placas', 'value' => pdf_val($m['placas'] ?? null, '')],
        ['label' => 'Tipo de mantenimiento', 'value' => pdf_val(isset($m['tipo']) ? ucfirst($m['tipo']) : null, '')],
        ['label' => 'Servicios', 'value' => pdf_val(mantenimiento_servicios_labels($m['servicios'] ?? (!empty($m['servicio']) ? [(string) $m['servicio']] : [])), '')],
        ['label' => 'Fecha programada', 'value' => pdf_date($m['fecha'] ?? null)],
        ['label' => 'Kilometraje', 'value' => isset($m['kilometraje']) ? number_format((int) $m['kilometraje']) . ' km' : ''],
        ['label' => 'Proveedor / taller', 'value' => pdf_val($m['proveedor_nombre'] ?? null, '')],
        ['label' => 'Costo estimado', 'value' => pdf_money($m['costo'] ?? null)],
        ['label' => 'Responsable', 'value' => pdf_val($m['responsable_nombre'] ?? null, '')],
    ]);
    ?>
    <p style="margin:4px 0 2px;font-size:7.5px;color:#64748b;text-transform:uppercase;">Descripción del trabajo</p>
    <div class="text-block"><?= e(pdf_val($m['descripcion'] ?? null)) ?: '&nbsp;' ?></div>
    <?php if (!empty($m['observaciones'])): ?>
    <p style="margin:4px 0 2px;font-size:7.5px;color:#64748b;text-transform:uppercase;">Observaciones</p>
    <div class="text-block"><?= e(pdf_val($m['observaciones'] ?? null)) ?></div>
    <?php endif; ?>
</div>

<?php if ($facturaEmbed !== null): ?>
<table style="page-break-before:always;width:100%;border-collapse:collapse;">
    <tr>
        <td style="text-align:center;vertical-align:middle;padding:0;">
            <img src="<?= $facturaEmbed['src'] ?>" width="<?= $facturaEmbed['width_mm'] ?>mm" height="<?= $facturaEmbed['height_mm'] ?>mm" alt="Factura">
        </td>
    </tr>
</table>
<?php $pdfSkipFooter = true; ?>
<?php elseif ($facturaEsPdf): ?>
<table style="page-break-before:always;width:100%;">
    <tr><td><p class="factura-note">La factura se cargó en PDF. Vuelva a subirla como imagen JPG/PNG para imprimirla aquí.</p></td></tr>
</table>
<?php elseif ($m === null): ?>
<table style="page-break-before:always;width:100%;">
    <tr><td><div class="factura-box">&nbsp;</div></td></tr>
</table>
<?php endif; ?>

<div class="section">
    <div class="section-title">Datos de factura</div>
    <?php
    pdf_render_fields([
        ['label' => 'RFC proveedor', 'value' => pdf_val($m['proveedor_rfc'] ?? null, '')],
        ['label' => 'Teléfono proveedor', 'value' => pdf_val($m['proveedor_telefono'] ?? null, '')],
        ['label' => 'Folio / serie', 'value' => pdf_val($m['factura_folio'] ?? null, '')],
        ['label' => 'Fecha factura', 'value' => pdf_date($m['factura_fecha'] ?? null)],
        ['label' => 'Folio fiscal (UUID)', 'value' => pdf_val($m['factura_uuid'] ?? null, '')],
        ['label' => 'Subtotal', 'value' => isset($m['factura_subtotal']) && $m['factura_subtotal'] !== null ? pdf_money($m['factura_subtotal']) : ''],
        ['label' => 'IVA', 'value' => isset($m['factura_iva']) && $m['factura_iva'] !== null ? pdf_money($m['factura_iva']) : ''],
        ['label' => 'Total', 'value' => isset($m['factura_total']) && $m['factura_total'] !== null ? pdf_money($m['factura_total']) : ''],
    ]);
    ?>
</div>

<div class="section">
    <div class="section-title">Autorización y cierre</div>
    <?php
    pdf_render_fields([
        ['label' => 'Autorizado por', 'value' => pdf_val($m['autorizado_por_nombre'] ?? null, '')],
        ['label' => 'Costo final', 'value' => pdf_money($m['costo'] ?? null)],
    ]);
    ?>
    <?php
    pdf_render_firmas([
        ['label' => 'Solicita el servicio', 'nombre' => $m['responsable_nombre'] ?? ''],
        ['label' => 'Autoriza (supervisor / transporte)', 'nombre' => $m['autorizado_por_nombre'] ?? ''],
        ['label' => 'Recibe unidad reparada', 'nombre' => ''],
    ]);
    ?>
</div>
<?php
$pdfBody = ob_get_clean();
require view_path('pdf/layout.php');
