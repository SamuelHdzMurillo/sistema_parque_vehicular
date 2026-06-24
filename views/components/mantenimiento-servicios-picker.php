<?php
/** @var list<array{tipo: string, nombre?: string}> $servicios */
/** @var list<string> $selected */
/** @var bool $puedeAgregar */
/** @var string $formId */
/** @var string $returnTo */
$servicios = $servicios ?? [];
$selected = $selected ?? [];
$required = !empty($required);
$puedeAgregar = !empty($puedeAgregar);
$formId = $formId ?? 'mantenimiento-servicio-form';
$returnTo = $returnTo ?? 'mantenimiento/create';
$openAgregar = !empty($openAgregar);
?>
<div class="servicios-picker-box">
    <div class="servicios-picker" data-servicios-mantenimiento role="group" aria-label="Servicios realizados">
        <?php if ($servicios === [] && !$puedeAgregar): ?>
        <p class="text-muted mb-0">Aún no hay servicios en la lista.</p>
        <?php else: ?>
        <?php foreach ($servicios as $s): ?>
        <?php $tipoServ = (string) ($s['tipo'] ?? ''); ?>
        <?php $label = (string) ($s['nombre'] ?? mantenimiento_servicio_label($tipoServ)); ?>
        <?php $isSelected = in_array($tipoServ, $selected, true); ?>
        <label class="servicios-picker-item">
            <input type="checkbox" name="servicios[]" value="<?= e($tipoServ) ?>" class="sr-only"
                <?= $isSelected ? 'checked' : '' ?>
                <?= $required ? 'data-servicio-required' : '' ?>>
            <span class="servicios-picker-chip<?= $isSelected ? ' is-selected' : '' ?>"><?= e($label) ?></span>
        </label>
        <?php endforeach; ?>

        <?php if ($puedeAgregar): ?>
        <details class="servicios-agregar-inline" id="agregar-servicio-mantenimiento" <?= $openAgregar ? 'open' : '' ?>>
            <summary class="servicios-picker-item servicios-picker-item--add" title="Agregar otro servicio">
                <span class="servicios-picker-chip servicios-picker-chip--add">+ Agregar</span>
            </summary>
            <div class="servicios-agregar-fields">
                <input type="text" form="<?= e($formId) ?>" name="nuevo_servicio[nombre]" class="form-control form-control-sm"
                       placeholder="Nombre del servicio (ej. Revisión de frenos)" required maxlength="100"
                       aria-label="Nombre del nuevo servicio">
                <input type="text" form="<?= e($formId) ?>" name="nuevo_servicio[tipo]" class="form-control form-control-sm"
                       placeholder="Código (opcional)" maxlength="50" pattern="[a-z][a-z0-9_]*"
                       aria-label="Código interno del servicio">
                <button type="submit" form="<?= e($formId) ?>" class="btn btn-accent btn-sm">Guardar</button>
            </div>
        </details>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<p class="form-hint text-muted mb-0 mt-1">
    Toque las etiquetas para seleccionarlas (verde con ✓). «+ Agregar» está en la misma fila para dar de alta otro servicio.
</p>
<script>
(function () {
    var picker = document.querySelector('[data-servicios-mantenimiento]');
    if (!picker) return;
    picker.addEventListener('change', function (e) {
        var input = e.target;
        if (input.type !== 'checkbox' || !input.closest('.servicios-picker-item')) return;
        var chip = input.parentElement && input.parentElement.querySelector('.servicios-picker-chip');
        if (chip) chip.classList.toggle('is-selected', input.checked);
    });
})();
</script>
