<?php
require_once view_path('pdf/helpers.php');

$c = $comision ?? null;
$pdfTitle = 'Orden de Comisión Vehicular';
$pdfSubtitle = $c ? ('Folio: ' . ($c['folio'] ?? '')) : 'Formato en blanco — Salida y regreso';

ob_start();
?>
<div class="section">
    <div class="section-title">Datos generales</div>
    <?php
    pdf_render_fields([
        ['label' => 'Folio', 'value' => pdf_val($c['folio'] ?? null, '')],
        ['label' => 'Fecha', 'value' => pdf_date($c['fecha'] ?? null)],
        ['label' => 'Identificador', 'value' => pdf_val($c['numero_economico'] ?? null, '')],
        ['label' => 'Placas', 'value' => pdf_val($c['placas'] ?? null, '')],
        ['label' => 'Área solicitante', 'value' => pdf_val($c['area_solicitante_nombre'] ?? null, '')],
        ['label' => 'Responsable del vehículo', 'value' => pdf_val($c['responsable_nombre'] ?? null, '')],
        ['label' => 'Conductor', 'value' => pdf_val($c['conductor_nombre'] ?? null, '')],
        ['label' => 'Estado', 'value' => pdf_val(isset($c['estado']) ? ucfirst(str_replace('_', ' ', $c['estado'])) : null, '')],
    ]);
    ?>
    <p style="margin:6px 0 2px;font-size:8px;color:#64748b;text-transform:uppercase;">Destino</p>
    <div class="text-block"><?= e(pdf_val($c['destino'] ?? null)) ?: '&nbsp;' ?></div>
    <p style="margin:6px 0 2px;font-size:8px;color:#64748b;text-transform:uppercase;">Motivo del viaje</p>
    <div class="text-block"><?= e(pdf_val($c['motivo'] ?? null)) ?: '&nbsp;' ?></div>
</div>

<div class="section">
    <div class="section-title">Control de salida</div>
    <?php
    pdf_render_fields([
        ['label' => 'Hora de salida', 'value' => pdf_time($c['hora_salida'] ?? null)],
        ['label' => 'Kilometraje salida', 'value' => isset($c['km_salida']) ? number_format((int) $c['km_salida']) : ''],
        ['label' => 'Combustible salida (%)', 'value' => isset($c['combustible_salida']) ? (string) $c['combustible_salida'] : ''],
        ['label' => 'Observaciones salida', 'value' => pdf_val($c['observaciones'] ?? null, '')],
    ]);
    ?>
    <?php
    pdf_render_firmas([
        ['label' => 'Firma del conductor', 'nombre' => $c['conductor_nombre'] ?? ''],
        ['label' => 'Firma responsable vehículo', 'nombre' => $c['responsable_nombre'] ?? ''],
        ['label' => 'Autoriza salida (supervisor)', 'nombre' => ''],
    ]);
    ?>
</div>

<div class="section">
    <div class="section-title">Control de regreso</div>
    <?php
    pdf_render_fields([
        ['label' => 'Hora de regreso', 'value' => pdf_time($c['hora_regreso'] ?? null)],
        ['label' => 'Kilometraje regreso', 'value' => isset($c['km_regreso']) ? number_format((int) $c['km_regreso']) : ''],
        ['label' => 'Km recorridos', 'value' => isset($c['km_recorridos']) ? number_format((int) $c['km_recorridos']) : ''],
        ['label' => 'Combustible regreso (%)', 'value' => isset($c['combustible_regreso']) ? (string) $c['combustible_regreso'] : ''],
        ['label' => 'Rendimiento (km/L)', 'value' => isset($c['rendimiento']) ? number_format((float) $c['rendimiento'], 2) : ''],
        ['label' => 'Litros consumidos', 'value' => isset($c['litros_consumidos']) ? number_format((float) $c['litros_consumidos'], 2) : ''],
    ]);
    ?>
    <p style="margin:6px 0 2px;font-size:8px;color:#64748b;text-transform:uppercase;">Observaciones de regreso</p>
    <div class="text-block">&nbsp;</div>
    <?php
    pdf_render_firmas([
        ['label' => 'Firma conductor (regreso)', 'nombre' => $c['conductor_nombre'] ?? '', 'firma' => $c['firma_digital'] ?? null],
        ['label' => 'Recibe vehículo', 'nombre' => $c['responsable_nombre'] ?? ''],
        ['label' => 'Vo. Bo. transporte', 'nombre' => ''],
    ]);
    ?>
</div>
<?php
$pdfBody = ob_get_clean();
require view_path('pdf/layout.php');
