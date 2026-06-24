<?php
/** @var array<string, mixed> $row */
/** @var 'global'|'vehiculo' $mode */
$row = $row ?? [];
$mode = $mode ?? 'global';
$formKey = (string) ($formKey ?? '');
$namePrefix = $mode === 'global' ? "config[{$formKey}]" : "vehiculo_config[{$formKey}]";
?>
<tr>
    <td><?= e($row['nombre'] ?? '') ?></td>
    <td>
        <div class="alerta-config-celda">
            <input type="number" name="<?= e($namePrefix) ?>[umbral_rojo_dias]" class="form-control form-control-sm" min="0"
                   value="<?= e((string) ($row['umbral_rojo_dias'] ?? '')) ?>" placeholder="365">
            <span>días</span>
        </div>
    </td>
    <td>
        <div class="alerta-config-celda">
            <input type="number" name="<?= e($namePrefix) ?>[umbral_amarillo_dias]" class="form-control form-control-sm" min="0"
                   value="<?= e((string) ($row['umbral_amarillo_dias'] ?? '')) ?>" placeholder="180">
            <span>días</span>
        </div>
    </td>
    <td>
        <div class="alerta-config-celda">
            <input type="number" name="<?= e($namePrefix) ?>[umbral_verde_dias]" class="form-control form-control-sm" min="0"
                   value="<?= e((string) ($row['umbral_verde_dias'] ?? '')) ?>" placeholder="90">
            <span>días</span>
        </div>
    </td>
</tr>
