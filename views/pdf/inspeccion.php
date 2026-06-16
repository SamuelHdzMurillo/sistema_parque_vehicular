<?php
require_once view_path('pdf/helpers.php');

$i = $inspeccion ?? null;
$checklistItems = $items ?? [];
$pdfTitle = 'Bitácora de Inspección Vehicular';
$pdfSubtitle = $i
    ? ('Vehículo ' . ($i['numero_economico'] ?? '') . ' · ' . pdf_date($i['fecha'] ?? null))
    : 'Formato en blanco — Checklist de 11 ítems';

$itemsByCode = [];
if ($i !== null) {
    foreach ($i['items'] ?? [] as $item) {
        $itemsByCode[$item['item_codigo']] = $item;
    }
}

ob_start();
?>
<div class="section">
    <div class="section-title">Datos de la inspección</div>
    <?php
    pdf_render_fields([
        ['label' => 'Identificador', 'value' => pdf_val($i['numero_economico'] ?? null, '')],
        ['label' => 'Fecha', 'value' => pdf_date($i['fecha'] ?? null)],
        ['label' => 'Kilometraje', 'value' => isset($i['kilometraje']) ? number_format((int) $i['kilometraje']) . ' km' : ''],
        ['label' => 'Responsable', 'value' => pdf_val($i['responsable_nombre'] ?? null, '')],
        ['label' => 'Resultado general', 'value' => pdf_val(isset($i['resultado_general']) ? ucfirst($i['resultado_general']) : null, '')],
        ['label' => 'Fecha registro', 'value' => $i ? format_datetime($i['created_at'] ?? null) : ''],
    ]);
    ?>
    <p style="margin:6px 0 2px;font-size:8px;color:#64748b;text-transform:uppercase;">Observaciones generales</p>
    <div class="text-block"><?= e(pdf_val($i['observaciones_generales'] ?? null)) ?: '&nbsp;' ?></div>
</div>

<div class="section">
    <div class="section-title">Checklist de inspección</div>
    <table class="data checklist">
        <thead>
            <tr>
                <th>Ítem</th>
                <th class="center">Bueno</th>
                <th class="center">Regular</th>
                <th class="center">Malo</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($checklistItems as $item): ?>
            <?php
                $codigo = $item['codigo'];
                $saved = $itemsByCode[$codigo] ?? null;
                $cal = $saved['calificacion'] ?? null;
            ?>
            <tr>
                <td><?= e($item['nombre']) ?></td>
                <td class="center"><?= $cal === 'bueno' ? '●' : '○' ?></td>
                <td class="center"><?= $cal === 'regular' ? '●' : '○' ?></td>
                <td class="center"><?= $cal === 'malo' ? '●' : '○' ?></td>
                <td><?= e($saved['observaciones'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$lucesCatalog = $luces_tablero ?? [];
$lucesOn = [];
if ($i !== null) {
    foreach ($i['luces_tablero'] ?? [] as $row) {
        $lucesOn[] = $row['luz_codigo'];
    }
}
?>
<?php if (!empty($lucesCatalog)): ?>
<div class="section">
    <div class="section-title">Luces del tablero encendidas</div>
    <table class="data" style="width:100%;border-collapse:separate;border-spacing:6px;">
        <tr>
            <?php $col = 0; foreach ($lucesCatalog as $luz): ?>
            <?php $isOn = in_array($luz['codigo'], $lucesOn, true); $col++; ?>
            <td style="width:25%;text-align:center;padding:8px;border:<?= $isOn ? '2px solid #f59e0b' : '1px solid #e2e8f0' ?>;border-radius:6px;background:<?= $isOn ? '#fef3c7' : '#f8fafc' ?>;vertical-align:top;">
                <?php
                $iconPath = public_path('assets/images/luces-tablero/' . $luz['icon']);
                if (is_file($iconPath)) {
                    echo '<img src="' . e($iconPath) . '" width="40" height="40" alt="">';
                }
                ?>
                <div style="font-size:7px;font-weight:<?= $isOn ? '700' : '600' ?>;margin-top:4px;color:<?= $isOn ? '#b45309' : '#475569' ?>;"><?= e($luz['nombre']) ?></div>
                <div style="font-size:6px;font-weight:<?= $isOn ? '700' : '400' ?>;color:<?= $isOn ? '#d97706' : '#94a3b8' ?>;"><?= $isOn ? '✔ ENCENDIDA' : '○ Apagada' ?></div>
            </td>
            <?php if ($col % 4 === 0): ?></tr><tr><?php endif; ?>
            <?php endforeach; ?>
        </tr>
    </table>
</div>
<?php endif; ?>

<div class="section">
    <div class="section-title">Firmas de conformidad</div>
    <?php
    pdf_render_firmas([
        ['label' => 'Firma responsable inspección', 'nombre' => $i['responsable_nombre'] ?? '', 'firma' => $i['firma_digital'] ?? null],
        ['label' => 'Firma del conductor', 'nombre' => ''],
        ['label' => 'Vo. Bo. supervisor', 'nombre' => ''],
    ]);
    ?>
</div>
<?php
$pdfBody = ob_get_clean();
require view_path('pdf/layout.php');
