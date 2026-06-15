<?php
require_once view_path('pdf/helpers.php');

$m = $mantenimiento ?? null;
$pdfTitle = 'Orden de Servicio — Mantenimiento';
$pdfSubtitle = $m ? ('Folio: ' . ($m['folio'] ?? '')) : 'Formato en blanco';

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
        ['label' => 'Fecha programada', 'value' => pdf_date($m['fecha'] ?? null)],
        ['label' => 'Kilometraje', 'value' => isset($m['kilometraje']) ? number_format((int) $m['kilometraje']) . ' km' : ''],
        ['label' => 'Proveedor / taller', 'value' => pdf_val($m['proveedor_nombre'] ?? null, '')],
        ['label' => 'Costo estimado', 'value' => pdf_money($m['costo'] ?? null)],
        ['label' => 'Responsable', 'value' => pdf_val($m['responsable_nombre'] ?? null, '')],
    ]);
    ?>
    <p style="margin:6px 0 2px;font-size:8px;color:#64748b;text-transform:uppercase;">Descripción del trabajo</p>
    <div class="text-block"><?= e(pdf_val($m['descripcion'] ?? null)) ?: '&nbsp;' ?></div>
    <p style="margin:6px 0 2px;font-size:8px;color:#64748b;text-transform:uppercase;">Observaciones</p>
    <div class="text-block"><?= e(pdf_val($m['observaciones'] ?? null)) ?: '&nbsp;' ?></div>
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
