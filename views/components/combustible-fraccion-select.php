<?php
$id = $id ?? 'combustible';
$name = $name ?? $id;
$label = $label ?? 'Combustible';
$valuePorcentaje = $valuePorcentaje ?? null;
$required = !empty($required);
$source = old_nonempty($name, null);
if ($source === null) {
    $source = $valuePorcentaje;
}
$porcentaje = combustible_fraccion_a_porcentaje($source);
if ($porcentaje === null && $required) {
    $porcentaje = 100.0;
}
$porcentajeInt = $porcentaje !== null ? (int) round($porcentaje) : null;
if ($porcentajeInt !== null) {
    $porcentajeInt = max(0, min(100, (int) (round($porcentajeInt / 25) * 25)));
} elseif ($required) {
    $porcentajeInt = 100;
}
$presets = [100, 75, 50, 25, 0];
$selected = (int) ($porcentajeInt ?? ($required ? 100 : 0));
?>
<div class="form-group" data-combustible-gauge data-combustible-name="<?= e($name) ?>">
    <label class="form-label" for="<?= e($id) ?>"><?= e($label) ?><?= $required ? ' <span class="required">*</span>' : '' ?></label>
    <div class="combustible-gauge">
        <div class="combustible-gauge-visual" aria-hidden="true">
            <div class="combustible-gauge-tank">
                <div class="combustible-gauge-fill" data-combustible-fill style="height: <?= $selected ?>%"></div>
            </div>
            <span class="combustible-gauge-value" data-combustible-display><?= $selected ?>%</span>
        </div>
        <div class="combustible-gauge-controls">
            <input type="range"
                   class="combustible-gauge-range"
                   min="0"
                   max="100"
                   step="25"
                   value="<?= $selected ?>"
                   data-combustible-range
                   oninput="var s=document.getElementById(<?= json_encode($id, JSON_THROW_ON_ERROR) ?>);if(s){s.value=String(Math.round(parseInt(this.value,10)/25)*25);s.dispatchEvent(new Event('change',{bubbles:true}));}"
                   aria-hidden="true"
                   tabindex="-1">
            <div class="combustible-gauge-marks" role="group" aria-label="Niveles rápidos del tanque">
                <?php foreach ($presets as $pct): ?>
                <button type="button"
                        class="combustible-gauge-mark<?= $selected === $pct ? ' is-active' : '' ?>"
                        data-combustible-preset="<?= $pct ?>"
                        onclick="var s=document.getElementById(<?= json_encode($id, JSON_THROW_ON_ERROR) ?>);if(s){s.value='<?= $pct ?>';s.dispatchEvent(new Event('change',{bubbles:true}));}"
                        aria-label="<?= $pct ?> por ciento">
                    <?= $pct ?>%
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <label class="form-label mt-2" for="<?= e($id) ?>">Nivel en porcentaje</label>
    <select id="<?= e($id) ?>"
            name="<?= e($name) ?>"
            class="form-select combustible-gauge-select"
            data-combustible-value
            <?= $required ? 'required' : '' ?>>
        <?php if (!$required): ?>
        <option value="">— Seleccione —</option>
        <?php endif; ?>
        <?php foreach ($presets as $pct): ?>
        <option value="<?= $pct ?>" <?= $selected === $pct ? 'selected' : '' ?>><?= $pct ?>%</option>
        <?php endforeach; ?>
    </select>
    <small class="form-hint text-muted">Use el medidor, los botones o la lista. Se guarda como porcentaje (0% a 100%).</small>
</div>
