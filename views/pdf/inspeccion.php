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

$lucesCatalog = $luces_tablero ?? [];
$lucesById = [];
foreach ($lucesCatalog as $luz) {
    $lucesById[$luz['codigo']] = $luz;
}

$lucesOn = [];
if ($i !== null) {
    foreach ($i['luces_tablero'] ?? [] as $row) {
        $lucesOn[] = $row['luz_codigo'];
    }
}

$renderLucesPdf = static function (array $codigos) use ($lucesById): string {
    if ($codigos === []) {
        return '<span class="luz-none">No tiene luces prendidas.</span>';
    }
    $parts = [];
    foreach ($codigos as $codigo) {
        $luz = $lucesById[$codigo] ?? null;
        if ($luz === null) {
            continue;
        }
        $iconPath = public_path('assets/images/luces-tablero/' . $luz['icon']);
        $img = is_file($iconPath) ? '<img src="' . e($iconPath) . '" width="14" height="14"> ' : '';
        $parts[] = '<span class="luz-item">' . $img . e($luz['nombre']) . '</span>';
    }
    return '<span class="luz-list">' . implode(' &nbsp; ', $parts) . '</span>';
};

ob_start();
?>
<style>
    .luz-list { font-size: 9.5px; line-height: 1.45; }
    .luz-item { white-space: nowrap; }
    .luz-item img { vertical-align: middle; }
    .luz-none { font-size: 9.5px; font-style: italic; color: #555; }
</style>

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

<?php if ($i !== null): ?>
<div class="section">
    <div class="section-title">Luces del tablero encendidas</div>
    <?= $renderLucesPdf($lucesOn) ?>
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
