<?php
require_once view_path('pdf/helpers.php');

$c = $carga ?? null;
$pdfTitle = 'Registro de Carga de Combustible';
$pdfSubtitle = $c ? ('Carga #' . ($c['id'] ?? '')) : 'Formato en blanco';

$ticketRuta = isset($c['factura_ruta']) ? (string) $c['factura_ruta'] : '';
$ticketEmbed = pdf_prepare_factura($ticketRuta !== '' ? $ticketRuta : null, true);
$ticketEsPdf = pdf_factura_is_pdf($ticketRuta !== '' ? $ticketRuta : null);
$ticketTieneArchivo = $ticketRuta !== '' && pdf_resolve_ticket_image_path($ticketRuta) !== null;

ob_start();
?>
<div class="section">
    <div class="section-title">Datos de la carga</div>
    <?php
    pdf_render_fields([
        ['label' => 'No. registro', 'value' => isset($c['id']) ? (string) $c['id'] : ''],
        ['label' => 'Fecha', 'value' => pdf_date($c['fecha'] ?? null)],
        ['label' => 'Vehículo', 'value' => pdf_val($c['numero_economico'] ?? null, '')],
        ['label' => 'Placas', 'value' => pdf_val($c['placas'] ?? null, '')],
        ['label' => 'Proveedor / gasolinera', 'value' => pdf_val($c['proveedor_nombre'] ?? null, '')],
        ['label' => 'Litros cargados', 'value' => isset($c['litros']) ? number_format((float) $c['litros'], 2) : ''],
        ['label' => 'Importe ($)', 'value' => pdf_money($c['importe'] ?? null)],
        ['label' => 'Kilometraje al cargar', 'value' => isset($c['kilometraje']) ? number_format((int) $c['kilometraje']) . ' km' : ''],
        ['label' => 'Rendimiento (km/L)', 'value' => isset($c['rendimiento']) ? number_format((float) $c['rendimiento'], 2) : ''],
        ['label' => 'Costo por km', 'value' => isset($c['costo_por_km']) ? '$' . number_format((float) $c['costo_por_km'], 4) : ''],
        ['label' => 'No. ticket / factura', 'value' => pdf_val($c['folio_ticket'] ?? null, '')],
        ['label' => 'Registrado por', 'value' => pdf_val($c['registrado_por_nombre'] ?? null, '')],
    ]);
    ?>
    <?php if (!empty($c['observaciones'])): ?>
    <p class="block-caption">Observaciones</p>
    <div class="text-block"><?= e(pdf_val($c['observaciones'] ?? null)) ?></div>
    <?php endif; ?>
</div>

<div class="section">
    <div class="section-title">Firmas de validación</div>
    <?php
    pdf_render_firmas([
        ['label' => 'Firma conductor', 'nombre' => ''],
        ['label' => 'Registra carga', 'nombre' => $c['registrado_por_nombre'] ?? ''],
        ['label' => 'Autoriza transporte', 'nombre' => ''],
    ]);
    ?>
</div>

<?php if ($ticketEmbed !== null): ?>
<div class="factura-page">
    <p class="factura-cap">Comprobante / ticket de carga</p>
    <div class="factura-viewport">
        <img src="<?= $ticketEmbed['src'] ?>" width="<?= $ticketEmbed['width_mm'] ?>mm" height="<?= $ticketEmbed['height_mm'] ?>mm" alt="Ticket">
    </div>
</div>
<?php $pdfSkipFooter = true; ?>
<?php elseif ($ticketEsPdf): ?>
<div class="factura-page">
    <p class="factura-cap">Comprobante / ticket de carga</p>
    <p class="factura-note">El ticket se registró como archivo PDF. Para que aparezca impreso en este documento, vuelva a registrar la carga subiendo una foto JPG o PNG del ticket. Puede consultar el PDF original desde el listado de combustible.</p>
</div>
<?php elseif ($c === null): ?>
<div class="factura-page">
    <p class="factura-cap">Comprobante / ticket de carga</p>
    <div class="factura-box">&nbsp;</div>
</div>
<?php elseif ($ticketRuta !== '' && !$ticketTieneArchivo): ?>
<div class="factura-page">
    <p class="factura-cap">Comprobante / ticket de carga</p>
    <p class="factura-note">No se pudo incluir la imagen del ticket. Verifique que el archivo siga disponible en el sistema.</p>
</div>
<?php endif; ?>
<?php
$pdfBody = ob_get_clean();
require view_path('pdf/layout.php');
