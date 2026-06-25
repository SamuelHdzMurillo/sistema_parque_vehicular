<?php
/** @var string $name */
/** @var string $label */
/** @var list<array{codigo: string, nombre: string}> $catalogo */
/** @var list<string> $selected */
/** @var string|null $hint */

$name = $name ?? 'herramientas_salida[]';
$label = $label ?? 'Herramientas';
$catalogo = $catalogo ?? [];
$selected = $selected ?? [];
$hint = $hint ?? null;
?>
<div class="form-group">
    <label class="form-label"><?= e($label) ?></label>
    <?php if ($hint !== null && $hint !== ''): ?>
    <p class="card-header-hint"><?= e($hint) ?></p>
    <?php endif; ?>
    <div class="checklist-grid">
        <?php foreach ($catalogo as $item): ?>
        <?php $cod = $item['codigo']; $isOn = in_array($cod, $selected, true); ?>
        <label class="checklist-item" style="cursor:pointer">
            <div class="checklist-item-name">
                <input type="checkbox" name="<?= e($name) ?>" value="<?= e($cod) ?>" <?= $isOn ? 'checked' : '' ?>>
                <?= e($item['nombre']) ?>
            </div>
        </label>
        <?php endforeach; ?>
    </div>
</div>
