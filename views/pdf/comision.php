<?php
require_once view_path('pdf/helpers.php');

$c = $comision ?? null;
$parte = $parte ?? 'completo';
$um = $ultimo_mantenimiento ?? null;
$lucesCatalogo = $luces_catalogo ?? [];
$lucesById = [];
foreach ($lucesCatalogo as $luz) {
    $lucesById[$luz['codigo']] = $luz;
}

$liquidosCatalogo = $liquidos_catalogo ?? [];
$nivelOpciones = $nivel_opciones ?? [];

$renderNivelesPdf = static function (array $niveles) use ($liquidosCatalogo, $nivelOpciones): string {
    if ($niveles === []) {
        return '<span class="luz-none">Sin registro de niveles.</span>';
    }
    $parts = [];
    foreach ($liquidosCatalogo as $liq) {
        $cod = $liq['codigo'];
        if (!isset($niveles[$cod])) {
            continue;
        }
        $txt = $nivelOpciones[$niveles[$cod]] ?? $niveles[$cod];
        $parts[] = '<span class="luz-item"><strong>' . e($liq['nombre']) . ':</strong> ' . e($txt) . '</span>';
    }
    return '<span class="luz-list">' . implode(' &nbsp; ', $parts) . '</span>';
};

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

$mostrarSalida = in_array($parte, ['salida', 'completo'], true);
$mostrarRegreso = in_array($parte, ['regreso', 'completo'], true);

$parteLabel = match ($parte) {
    'salida' => ' — Control de salida',
    'regreso' => ' — Control de regreso',
    default => '',
};

$pdfTitle = 'Orden de Comisión Vehicular' . $parteLabel;
$pdfSubtitle = $c ? ('Folio: ' . ($c['folio'] ?? '')) : 'Formato en blanco — Salida y regreso';

ob_start();
?>
<style>
    /* Estilos compactos para que la comisión quepa en una sola página */
    .cmp .section { margin-top: 5px; margin-bottom: 2px; }
    .cmp .section-title { font-size: 9px; padding-bottom: 2px; margin-bottom: 2px; }
    .cmp .grid { width: 100%; border-collapse: collapse; }
    .cmp .grid td {
        vertical-align: top;
        padding: 1px 8px 3px 0;
        width: 33.33%;
    }
    .cmp .lbl { display: block; font-size: 7px; color: #6c757d; text-transform: uppercase; letter-spacing: .2px; }
    .cmp .val {
        display: block; font-size: 9.5px; min-height: 12px;
        border-bottom: 1px solid #ccc; padding-bottom: 1px;
    }
    .cmp .inline-block { margin-top: 2px; }
    .cmp .inline-block .lbl { margin-bottom: 1px; }
    .cmp .inline-block .box {
        border: 1px solid #ccc; padding: 2px 6px; min-height: 12px; font-size: 9.5px;
    }
    .cmp .two-col td { width: 50%; vertical-align: top; padding-right: 10px; }
    .cmp .firmas-table { margin-top: 8px; }
    .cmp .firma-linea { height: 44px; margin-bottom: 2px; }
    .cmp .firma-img { max-height: 40px; }
    .cmp .firma-label { font-size: 7px; }
    .cmp .firma-nombre { font-size: 8px; margin-top: 1px; }
    .cmp .luz-block-label { font-size: 7px; color: #6c757d; text-transform: uppercase; margin: 2px 0 0; }
    .cmp .luz-list { font-size: 8.5px; line-height: 1.45; }
    .cmp .luz-item { white-space: nowrap; }
    .cmp .luz-item img { vertical-align: middle; }
    .cmp .luz-none { font-size: 9px; font-style: italic; color: #555; }
    .cmp .leyenda {
        margin-top: 6px; border: 1px solid #999; background: #f8f8f8;
        padding: 4px 8px; font-size: 8px; line-height: 1.35; text-align: justify;
    }
</style>

<?php
/** Render compacto de campos en 3 columnas. */
$campo = static function (string $label, string $value): string {
    $value = trim($value);
    return '<td><span class="lbl">' . e($label) . '</span><span class="val">'
        . ($value !== '' ? e($value) : '&nbsp;') . '</span></td>';
};
$filaCampos = static function (array $campos, callable $campo, int $cols = 3): void {
    foreach (array_chunk($campos, $cols) as $row) {
        echo '<table class="grid"><tr>';
        foreach ($row as $c) {
            echo $campo($c[0], $c[1]);
        }
        for ($i = count($row); $i < $cols; $i++) {
            echo '<td></td>';
        }
        echo '</tr></table>';
    }
};
?>

<div class="cmp">
    <div class="section">
        <div class="section-title">Datos de la comisión</div>
        <?php $filaCampos([
            ['Folio', pdf_val($c['folio'] ?? null)],
            ['Fecha de la revista', pdf_date($c['fecha'] ?? null)],
            ['Hora de la comisión', pdf_time($c['hora_salida'] ?? null)],
            ['Identificador', pdf_val($c['numero_economico'] ?? null)],
            ['Placas', pdf_val($c['placas'] ?? null)],
            ['Estado', pdf_val(isset($c['estado']) ? ucfirst(str_replace('_', ' ', $c['estado'])) : null)],
            ['Área que solicita el vehículo', pdf_val($c['area_solicitante_nombre'] ?? null)],
            ['Responsable del vehículo', pdf_val($c['responsable_nombre'] ?? null)],
            ['Conductor', pdf_val($c['conductor_nombre'] ?? null)],
            ['Responsable de regreso (trae el vehículo)', pdf_val($c['responsable_regreso_nombre'] ?? null)],
        ], $campo); ?>
        <table class="grid two-col" style="margin-top:4px;">
            <tr>
                <td>
                    <div class="inline-block"><span class="lbl">Destino</span>
                        <div class="box"><?= e(pdf_val($c['destino'] ?? null)) ?: '&nbsp;' ?></div>
                    </div>
                </td>
                <td>
                    <div class="inline-block"><span class="lbl">Motivo del viaje</span>
                        <div class="box"><?= e(pdf_val($c['motivo'] ?? null)) ?: '&nbsp;' ?></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Último mantenimiento realizado</div>
        <?php if ($um !== null): ?>
        <?php $filaCampos([
            ['Folio', pdf_val($um['folio'] ?? null)],
            ['Fecha', pdf_date($um['fecha'] ?? null)],
            ['Tipo', pdf_val(isset($um['tipo']) ? ucfirst((string) $um['tipo']) : null)],
            ['Kilometraje', isset($um['kilometraje']) ? number_format((int) $um['kilometraje']) . ' km' : ''],
        ], $campo); ?>
        <?php else: ?>
        <div class="box" style="border:1px solid #ccc;padding:4px 6px;font-size:9.5px;">Sin mantenimientos finalizados registrados para este vehículo.</div>
        <?php endif; ?>
    </div>

    <?php if ($mostrarSalida): ?>
    <div class="section">
        <div class="section-title">Control de salida</div>
        <?php $filaCampos([
            ['Hora de salida', pdf_time($c['hora_salida'] ?? null)],
            ['Kilometraje salida', isset($c['km_salida']) ? number_format((int) $c['km_salida']) : ''],
            ['Combustible salida', isset($c['combustible_salida']) ? combustible_porcentaje_a_fraccion($c['combustible_salida']) : ''],
        ], $campo); ?>
        <div class="inline-block"><span class="lbl">Observaciones de salida</span>
            <div class="box"><?= e(pdf_val($c['observaciones'] ?? null)) ?: '&nbsp;' ?></div>
        </div>
        <div class="luz-block-label">Niveles de líquidos (salida)</div>
        <?= $renderNivelesPdf($c['niveles_salida'] ?? []) ?>
        <div class="luz-block-label">Luces del tablero encendidas (salida)</div>
        <?= $renderLucesPdf($c['luces_salida'] ?? []) ?>
        <?php pdf_render_firmas([
            ['label' => 'Firma del conductor', 'nombre' => $c['conductor_nombre'] ?? ''],
            ['label' => 'Firma responsable vehículo', 'nombre' => $c['responsable_nombre'] ?? ''],
            ['label' => 'Autoriza salida (supervisor)', 'nombre' => ''],
        ]); ?>
    </div>
    <?php endif; ?>

    <?php if ($mostrarRegreso): ?>
    <div class="section">
        <div class="section-title">Control de regreso</div>
        <?php $filaCampos([
            ['Hora de regreso', pdf_time($c['hora_regreso'] ?? null)],
            ['Kilometraje regreso', isset($c['km_regreso']) ? number_format((int) $c['km_regreso']) : ''],
            ['Km recorridos', isset($c['km_recorridos']) ? number_format((int) $c['km_recorridos']) : ''],
            ['Combustible regreso', isset($c['combustible_regreso']) ? combustible_porcentaje_a_fraccion($c['combustible_regreso']) : ''],
            ['Rendimiento (km/L)', isset($c['rendimiento']) ? number_format((float) $c['rendimiento'], 2) : ''],
            ['Litros consumidos', isset($c['litros_consumidos']) ? number_format((float) $c['litros_consumidos'], 2) : ''],
        ], $campo); ?>
        <div class="luz-block-label">Niveles de líquidos (regreso)</div>
        <?= $renderNivelesPdf($c['niveles_regreso'] ?? []) ?>
        <div class="luz-block-label">Luces del tablero encendidas (regreso)</div>
        <?= $renderLucesPdf($c['luces_regreso'] ?? []) ?>
        <?php pdf_render_firmas([
            ['label' => 'Firma conductor (regreso)', 'nombre' => $c['conductor_nombre'] ?? '', 'firma' => $c['firma_digital'] ?? null],
            ['label' => 'Recibe vehículo', 'nombre' => $c['responsable_regreso_nombre'] ?? ($c['responsable_nombre'] ?? '')],
            ['label' => 'Vo. Bo. transporte', 'nombre' => ''],
        ]); ?>
    </div>
    <?php endif; ?>

    <div class="leyenda">
        El vehículo debe entregarse en las mismas condiciones físicas y mecánicas en que fue recibido.
        De no ser así, el responsable asignado se hará cargo de los daños, faltantes o desperfectos ocasionados.
    </div>
</div>
<?php
$pdfBody = ob_get_clean();
require view_path('pdf/layout.php');
