<?php
/** @var array<string, mixed> $row */
/** @var 'global'|'vehiculo' $mode */
$row = $row ?? [];
$mode = $mode ?? 'global';
$formKey = (string) ($formKey ?? '');
$namePrefix = $mode === 'global' ? "config[{$formKey}]" : "vehiculo_config[{$formKey}]";
$tipo = (string) ($row['tipo'] ?? '');
$unidad = (string) ($row['unidad'] ?? 'km');
$esKm = $unidad === 'km';
$unidadLabel = $esKm ? 'km' : 'días';

$avisoValor = $esKm ? ($row['umbral_rojo'] ?? '') : ($row['umbral_verde'] ?? '');
$atencionValor = $row['umbral_amarillo'] ?? '';
$urgenteValor = $esKm ? ($row['umbral_verde'] ?? '') : ($row['umbral_rojo'] ?? '');
$avisoCampo = $esKm ? 'umbral_rojo' : 'umbral_verde';
$urgenteCampo = $esKm ? 'umbral_verde' : 'umbral_rojo';
?>
<tr class="alerta-config-row">
    <td class="alerta-config-row-nombre">
        <?php if ($mode === 'global'): ?>
        <input type="text" name="<?= e($namePrefix) ?>[nombre]" class="form-control form-control-sm"
               value="<?= e($row['nombre'] ?? '') ?>" aria-label="Nombre">
        <?php else: ?>
        <strong><?= e($row['nombre'] ?? '') ?></strong>
        <?php endif; ?>
    </td>
    <td class="alerta-config-row-resumen">
        <span class="alerta-config-resumen-text"><?= e($esKm ? alerta_config_resumen_km($row) : alerta_config_resumen_doc($row)) ?></span>
    </td>
    <td class="alerta-config-row-switch text-center">
        <?php if ($mode === 'vehiculo'): ?>
        <label class="alerta-config-check" title="Usar valores distintos para este vehículo">
            <input type="checkbox" name="<?= e($namePrefix) ?>[personalizado]" value="1"
                   <?= !empty($row['personalizado']) ? 'checked' : '' ?>>
            <span class="sr-only">Personalizar</span>
        </label>
        <?php else: ?>
        <label class="alerta-config-check" title="Alerta activa">
            <input type="checkbox" name="<?= e($namePrefix) ?>[activo]" value="1"
                   <?= !empty($row['activo']) ? 'checked' : '' ?>>
            <span class="sr-only">Activa</span>
        </label>
        <?php endif; ?>
    </td>
    <td>
        <div class="alerta-config-celda">
            <input type="number" name="<?= e($namePrefix) ?>[<?= e($avisoCampo) ?>]" class="form-control form-control-sm" min="0"
                   value="<?= e((string) $avisoValor) ?>">
            <span><?= e($unidadLabel) ?></span>
        </div>
    </td>
    <td>
        <div class="alerta-config-celda">
            <input type="number" name="<?= e($namePrefix) ?>[umbral_amarillo]" class="form-control form-control-sm" min="0"
                   value="<?= e((string) $atencionValor) ?>">
            <span><?= e($unidadLabel) ?></span>
        </div>
    </td>
    <td>
        <div class="alerta-config-celda">
            <input type="number" name="<?= e($namePrefix) ?>[<?= e($urgenteCampo) ?>]" class="form-control form-control-sm" min="0"
                   value="<?= e((string) $urgenteValor) ?>">
            <span><?= e($unidadLabel) ?></span>
        </div>
    </td>
</tr>
