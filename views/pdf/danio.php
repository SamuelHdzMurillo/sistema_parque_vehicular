<?php
require_once view_path('pdf/helpers.php');

$d = $danio ?? null;
$pdfTitle = 'Reporte de Daño Vehicular';
$pdfSubtitle = $d ? ('Reporte #' . ($d['id'] ?? '')) : 'Formato en blanco';

ob_start();
?>
<div class="section">
    <div class="section-title">Datos del reporte</div>
    <?php
    pdf_render_fields([
        ['label' => 'No. reporte', 'value' => isset($d['id']) ? (string) $d['id'] : ''],
        ['label' => 'Fecha reporte', 'value' => $d ? format_datetime($d['created_at'] ?? null) : ''],
        ['label' => 'Vehículo', 'value' => pdf_val($d['numero_economico'] ?? null, '')],
        ['label' => 'Placas', 'value' => pdf_val($d['placas'] ?? null, '')],
        ['label' => 'Tipo de daño', 'value' => pdf_val(isset($d['tipo_dano']) ? ucfirst($d['tipo_dano']) : null, '')],
        ['label' => 'Estado', 'value' => pdf_val(isset($d['estado']) ? ucfirst(str_replace('_', ' ', $d['estado'])) : null, '')],
        ['label' => 'Ubicación en vehículo', 'value' => pdf_val($d['ubicacion'] ?? null, '')],
        ['label' => 'Reportado por', 'value' => pdf_val($d['reportado_por_nombre'] ?? null, '')],
    ]);
    ?>
    <p style="margin:6px 0 2px;font-size:8px;color:#64748b;text-transform:uppercase;">Descripción detallada</p>
    <div class="text-block"><?= e(pdf_val($d['descripcion'] ?? null)) ?: '&nbsp;' ?></div>
</div>

<div class="section">
    <div class="section-title">Evidencia y seguimiento</div>
    <p style="font-size:9px;color:#64748b;margin:0 0 6px;">
        Adjunte fotografías del daño al entregar este formato. En sistema digital las fotos quedan registradas en el expediente.
    </p>
    <table class="data">
        <thead><tr><th>Fecha</th><th>Estado anterior</th><th>Estado nuevo</th><th>Comentario</th></tr></thead>
        <tbody>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">Firmas</div>
    <?php
    pdf_render_firmas([
        ['label' => 'Firma quien reporta', 'nombre' => $d['reportado_por_nombre'] ?? ''],
        ['label' => 'Vo. Bo. supervisor', 'nombre' => ''],
        ['label' => 'Recibe taller / evaluación', 'nombre' => ''],
    ]);
    ?>
</div>
<?php
$pdfBody = ob_get_clean();
require view_path('pdf/layout.php');
