<?php
$id = $id ?? 'combustible';
$name = $name ?? $id;
$label = $label ?? 'Combustible';
$valuePorcentaje = $valuePorcentaje ?? null;
$required = !empty($required);
$source = old($name, null);
if ($source === null || $source === '') {
    $source = $valuePorcentaje;
}
$selected = combustible_input_a_fraccion($source);
if ($selected === '' && $required) {
    $selected = '4/4';
}
?>
<div class="form-group">
    <label class="form-label" for="<?= e($id) ?>"><?= e($label) ?><?= $required ? ' <span class="required">*</span>' : '' ?></label>
    <select id="<?= e($id) ?>" name="<?= e($name) ?>" class="form-select" <?= $required ? 'required' : '' ?> data-combustible-fraccion>
        <?php if (!$required): ?>
        <option value="">— Seleccione —</option>
        <?php endif; ?>
        <?php foreach (combustible_fracciones_opciones() as $valor => $texto): ?>
        <option value="<?= e($valor) ?>" <?= $selected === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
        <?php endforeach; ?>
    </select>
    <small class="form-hint text-muted">Nivel del tanque en cuartos: 1/4, 1/2, 3/4 o lleno (4/4).</small>
</div>
