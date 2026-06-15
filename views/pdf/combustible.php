<?php
require_once view_path('pdf/helpers.php');

$c = $carga ?? null;
$pdfTitle = 'Registro de Carga de Combustible';
$pdfSubtitle = $c ? ('Carga #' . ($c['id'] ?? '')) : 'Formato en blanco';

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
        ['label' => 'Registrado por', 'value' => pdf_val($c['registrado_por_nombre'] ?? null, '')],
    ]);
    ?>
    <p style="margin:6px 0 2px;font-size:8px;color:#64748b;text-transform:uppercase;">No. ticket / factura</p>
    <div class="text-block">&nbsp;</div>
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
<?php
$pdfBody = ob_get_clean();
require view_path('pdf/layout.php');
