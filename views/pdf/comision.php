<?php
require_once view_path('pdf/helpers.php');

$c = $comision ?? null;
$parte = $parte ?? 'completo';
$um = $ultimo_mantenimiento ?? null;
$vencimientosRevistaTarjeta = $vencimientos_revista_tarjeta ?? null;
$lucesCatalogo = $luces_catalogo ?? [];
$lucesById = [];
foreach ($lucesCatalogo as $luz) {
    $lucesById[$luz['codigo']] = $luz;
}

$liquidosCatalogo = $liquidos_catalogo ?? [];
$nivelOpciones = $nivel_opciones ?? [];
$herramientasCatalogo = $herramientas_catalogo ?? [];
$herramientasByCodigo = [];
foreach ($herramientasCatalogo as $herr) {
    $herramientasByCodigo[$herr['codigo']] = $herr['nombre'];
}

$renderHerramientasPdf = static function (array $tipos) use ($herramientasByCodigo): string {
    if ($tipos === []) {
        return '<span class="luz-none">Ninguna registrada.</span>';
    }
    $parts = [];
    foreach ($tipos as $tipo) {
        $nombre = $herramientasByCodigo[$tipo] ?? ucfirst(str_replace('_', ' ', (string) $tipo));
        $parts[] = '<span class="luz-item">' . e($nombre) . '</span>';
    }
    return '<span class="luz-list">' . implode(' &nbsp; ', $parts) . '</span>';
};

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
$esCompleto = $mostrarSalida && $mostrarRegreso;

$formatVencimientoRevistaTarjeta = static function (?array $vencimientos): string {
    if ($vencimientos === null) {
        return '';
    }
    $parts = [];
    if (!empty($vencimientos['tarjeta_circulacion'])) {
        $parts[] = pdf_date($vencimientos['tarjeta_circulacion']);
    }
    if (!empty($vencimientos['verificacion'])) {
        $parts[] = pdf_date($vencimientos['verificacion']);
    }
    return implode(' / ', $parts);
};

$parteLabel = match ($parte) {
    'salida' => ' — Control de salida',
    'regreso' => ' — Control de regreso',
    default => '',
};

$pdfTitle = 'Orden de Comisión Vehicular' . $parteLabel;
$pdfSubtitle = $c ? ('Número de oficio de comisión: ' . ($c['folio'] ?? '')) : 'Formato en blanco — Salida y regreso';

$leyendaEntrega = 'El vehículo debe entregarse en las mismas condiciones físicas y mecánicas en que fue recibido al término de la comisión en el edificio de Dirección General de CECyTE BCS. '
    . 'De no ser así, el responsable asignado se hará cargo de los daños, faltantes o desperfectos ocasionados.';

ob_start();
?>
<style>
    /* Estilos compactos: salida en hoja 1 y regreso en hoja 2 (documento completo) */
    .cmp .section { margin-top: 5px; margin-bottom: 2px; }
    .cmp .section-title { font-size: 11px; padding-bottom: 2px; margin-bottom: 3px; }
    .cmp .grid { width: 100%; border-collapse: collapse; }
    .cmp .grid td {
        vertical-align: top;
        padding: 2px 8px 4px 0;
        width: 33.33%;
    }
    .cmp .lbl {
        display: block;
        font-size: 8.5px;
        font-weight: bold;
        color: #000;
        text-transform: uppercase;
        letter-spacing: .2px;
    }
    .cmp .val {
        display: block; font-size: 11px; min-height: 14px;
        border-bottom: 1.5px solid #000; padding-bottom: 1px;
    }
    .cmp .inline-block { margin-top: 2px; }
    .cmp .inline-block .lbl { margin-bottom: 1px; }
    .cmp .inline-block .box {
        border: 1.5px solid #000; padding: 5px 6px; min-height: 14px; font-size: 11px;
    }
    .cmp .two-col td { width: 50%; vertical-align: top; padding-right: 10px; }
    .cmp .firmas-table { margin-top: 8px; }
    .cmp .firma-label { font-size: 8.5px; }
    .cmp .firma-espacio { height: 50px; }
    .cmp .firma-img { max-height: 46px; }
    .cmp .firma-nombre { font-size: 10px; margin-top: 3px; }
    .cmp .luz-block-label {
        font-size: 8.5px;
        font-weight: bold;
        color: #000;
        text-transform: uppercase;
        margin: 3px 0 1px;
    }
    .cmp .luz-list { font-size: 10px; line-height: 1.45; }
    .cmp .luz-item { white-space: nowrap; }
    .cmp .luz-item img { vertical-align: middle; }
    .cmp .luz-none { font-size: 10.5px; font-style: italic; color: #000; }
    .cmp .leyenda {
        margin-top: 6px;
        border: 2px solid #000;
        background: #fff;
        padding: 5px 8px;
        font-size: 9.5px;
        font-weight: bold;
        line-height: 1.4;
        text-align: justify;
        page-break-inside: avoid;
    }
    .cmp .pagina-regreso {
        page-break-before: always;
    }
    .cmp .bloque-salida,
    .cmp .bloque-regreso {
        page-break-inside: avoid;
    }
    .cmp .mini-header {
        border-bottom: 2px solid #000;
        padding-bottom: 4px;
        margin-bottom: 6px;
        font-size: 9.5px;
        line-height: 1.45;
    }
    .cmp .mini-header strong {
        font-size: 10.5px;
        text-transform: uppercase;
    }
    .cmp .mini-header span {
        display: inline-block;
        margin-right: 10px;
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
    <?php if ($mostrarSalida): ?>
    <div class="section">
        <div class="section-title">Datos de la comisión</div>
        <?php $filaCampos([
            ['Número de oficio de comisión', pdf_val($c['folio'] ?? null)],
            ['Fecha salida de comisión', pdf_date($c['fecha'] ?? null)],
            ['Vencimiento revista / tarjeta de circulación', $formatVencimientoRevistaTarjeta($vencimientosRevistaTarjeta)],
            ['Hora de la comisión', pdf_time($c['hora_salida'] ?? null)],
            ['Identificador', pdf_val($c['numero_economico'] ?? null)],
            ['Placas', pdf_val($c['placas'] ?? null)],
            ['Estado', pdf_val(isset($c['estado']) ? ucfirst(str_replace('_', ' ', $c['estado'])) : null)],
            ['Área que solicita el vehículo', pdf_val($c['area_solicitante_nombre'] ?? null)],
            ['Responsable del vehículo', pdf_val($c['responsable_nombre'] ?? null)],
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
        <div class="box" style="border:1.5px solid #000;padding:4px 6px;font-size:11px;">Sin mantenimientos finalizados registrados para este vehículo.</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($mostrarSalida): ?>
    <div class="section bloque-salida">
        <div class="section-title">Control de salida</div>
        <?php $filaCampos([
            ['Hora de salida', pdf_time($c['hora_salida'] ?? null)],
            ['Kilometraje salida', isset($c['km_salida']) ? number_format((int) $c['km_salida']) : ''],
            ['Combustible salida', isset($c['combustible_salida']) ? combustible_fraccion_etiqueta($c['combustible_salida']) : ''],
        ], $campo); ?>
        <div class="inline-block"><span class="lbl">Observaciones de salida</span>
            <div class="box"><?= e(pdf_val($c['observaciones'] ?? null)) ?: '&nbsp;' ?></div>
        </div>
        <div class="luz-block-label">Niveles de líquidos (salida)</div>
        <?= $renderNivelesPdf($c['niveles_salida'] ?? []) ?>
        <div class="luz-block-label">Luces del tablero encendidas (salida)</div>
        <?= $renderLucesPdf($c['luces_salida'] ?? []) ?>
        <div class="luz-block-label">Herramientas entregadas en salida</div>
        <?= $renderHerramientasPdf($c['herramientas_salida'] ?? []) ?>
        <?php pdf_render_firmas([
            ['label' => 'Firma del conductor', 'nombre' => $c['conductor_nombre'] ?? ''],
            ['label' => 'Firma responsable vehículo', 'nombre' => $c['responsable_nombre'] ?? ''],
            ['label' => 'Autoriza salida (supervisor)', 'nombre' => ''],
        ]); ?>
        <?php if ($esCompleto): ?>
        <div class="leyenda"><?= e($leyendaEntrega) ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($mostrarRegreso): ?>
    <div class="<?= $esCompleto ? 'pagina-regreso' : '' ?>">
        <?php if ($esCompleto): ?>
        <div class="mini-header">
            <strong>Control de regreso</strong><br>
            <span><strong>Número de oficio de comisión:</strong> <?= e(pdf_val($c['folio'] ?? null)) ?: '—' ?></span>
            <span><strong>Vehículo:</strong> <?= e(pdf_val($c['numero_economico'] ?? null)) ?: '—' ?></span>
            <span><strong>Placas:</strong> <?= e(pdf_val($c['placas'] ?? null)) ?: '—' ?></span>
            <span><strong>Conductor:</strong> <?= e(pdf_val($c['conductor_nombre'] ?? null)) ?: '—' ?></span>
            <span><strong>Destino:</strong> <?= e(pdf_val($c['destino'] ?? null)) ?: '—' ?></span>
        </div>
        <?php elseif (!$mostrarSalida): ?>
        <div class="section">
            <div class="section-title">Datos de la comisión</div>
            <?php $filaCampos([
                ['Número de oficio de comisión', pdf_val($c['folio'] ?? null)],
                ['Fecha salida de comisión', pdf_date($c['fecha'] ?? null)],
                ['Vencimiento revista / tarjeta de circulación', $formatVencimientoRevistaTarjeta($vencimientosRevistaTarjeta)],
                ['Identificador', pdf_val($c['numero_economico'] ?? null)],
                ['Placas', pdf_val($c['placas'] ?? null)],
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
        <?php endif; ?>

        <div class="section bloque-regreso">
            <div class="section-title">Control de regreso</div>
            <?php $filaCampos([
                ['Hora de regreso', pdf_time($c['hora_regreso'] ?? null)],
                ['Kilometraje regreso', isset($c['km_regreso']) ? number_format((int) $c['km_regreso']) : ''],
                ['Km recorridos', isset($c['km_recorridos']) ? number_format((int) $c['km_recorridos']) : ''],
                ['Combustible regreso', isset($c['combustible_regreso']) ? combustible_fraccion_etiqueta($c['combustible_regreso']) : ''],
                ['Rendimiento (km/L)', isset($c['rendimiento']) ? number_format((float) $c['rendimiento'], 2) : ''],
                ['Litros consumidos', isset($c['litros_consumidos']) ? number_format((float) $c['litros_consumidos'], 2) : ''],
            ], $campo); ?>
            <div class="luz-block-label">Niveles de líquidos (regreso)</div>
            <?= $renderNivelesPdf($c['niveles_regreso'] ?? []) ?>
            <div class="luz-block-label">Luces del tablero encendidas (regreso)</div>
            <?= $renderLucesPdf($c['luces_regreso'] ?? []) ?>
            <div class="luz-block-label">Herramientas que regresaron</div>
            <?= $renderHerramientasPdf($c['herramientas_regreso'] ?? []) ?>
            <?php pdf_render_firmas([
                ['label' => 'Firma conductor (regreso)', 'nombre' => $c['conductor_nombre'] ?? '', 'firma' => $c['firma_digital'] ?? null],
                ['label' => 'Recibe vehículo', 'nombre' => ''],
            ]); ?>
            <?php if ($esCompleto): ?>
            <div class="leyenda"><?= e($leyendaEntrega) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$esCompleto): ?>
    <div class="leyenda"><?= e($leyendaEntrega) ?></div>
    <?php endif; ?>
</div>
<?php
$pdfBody = ob_get_clean();
require view_path('pdf/layout.php');
