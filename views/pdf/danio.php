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
    <p class="block-caption">Descripción detallada</p>
    <div class="text-block"><?= e(pdf_val($d['descripcion'] ?? null)) ?: '&nbsp;' ?></div>
</div>

<?php $fotos = $fotos ?? []; $seguimiento = $seguimiento ?? []; ?>
<div class="section">
    <div class="section-title">Evidencia fotográfica</div>
    <?php if (!empty($fotos)): ?>
    <table style="width:100%;border-collapse:collapse;">
        <tr>
        <?php foreach ($fotos as $i => $f): ?>
            <?php
            $uri = pdf_image_file_to_data_uri(storage_path('uploads/' . ltrim((string) $f['ruta'], '/')));
            ?>
            <td style="width:25%;padding:3px;vertical-align:top;text-align:center;">
                <?php if ($uri !== ''): ?>
                <img src="<?= $uri ?>" style="width:100%;height:90px;object-fit:cover;border:2px solid #000;">
                <?php endif; ?>
            </td>
            <?php if (($i + 1) % 4 === 0): ?></tr><tr><?php endif; ?>
        <?php endforeach; ?>
        </tr>
    </table>
    <?php else: ?>
    <p style="font-size:9px;font-weight:bold;color:#000;margin:0 0 6px;">
        Sin fotografías registradas. Adjunte fotografías del daño al entregar este formato.
    </p>
    <?php endif; ?>
</div>

<div class="section">
    <div class="section-title">Seguimiento del daño</div>
    <table class="data">
        <thead><tr><th>Fecha</th><th>Estado anterior</th><th>Estado nuevo</th><th>Comentario</th><th>Registró</th></tr></thead>
        <tbody>
            <?php if (!empty($seguimiento)): ?>
                <?php foreach ($seguimiento as $s): ?>
                <tr>
                    <td><?= e(format_datetime($s['created_at'] ?? null)) ?></td>
                    <td><?= e(ucfirst(str_replace('_', ' ', (string) ($s['estado_anterior'] ?? '')))) ?></td>
                    <td><?= e(ucfirst(str_replace('_', ' ', (string) ($s['estado_nuevo'] ?? '')))) ?></td>
                    <td><?= e(pdf_val($s['comentario'] ?? null)) ?: '&nbsp;' ?></td>
                    <td><?= e(pdf_val($s['usuario_nombre'] ?? null)) ?: '&nbsp;' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
                <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
            <?php endif; ?>
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
