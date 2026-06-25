<?php
/** @var string $name */
/** @var string $label */
/** @var list<array{codigo: string, nombre: string}> $catalogo */
/** @var list<string> $selected */
/** @var string|null $hint */
/** @var bool $allowCustom */

$name = $name ?? 'herramientas_salida[]';
$label = $label ?? 'Herramientas';
$catalogo = $catalogo ?? [];
$selected = $selected ?? [];
$hint = $hint ?? null;
$allowCustom = $allowCustom ?? true;
$catalogCodes = array_column($catalogo, 'codigo');
$customSelected = array_values(array_filter($selected, static fn ($cod) => !in_array($cod, $catalogCodes, true)));
?>
<div class="form-group" data-herramientas-checklist data-catalog-codes="<?= e(json_encode($catalogCodes, JSON_UNESCAPED_UNICODE)) ?>">
    <label class="form-label"><?= e($label) ?></label>
    <?php if ($hint !== null && $hint !== ''): ?>
    <p class="card-header-hint"><?= e($hint) ?></p>
    <?php endif; ?>
    <div class="checklist-grid" data-herramientas-catalog>
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
    <?php if ($allowCustom): ?>
    <div class="checklist-grid mt-2" data-herramientas-custom>
        <?php foreach ($customSelected as $cod): ?>
        <label class="checklist-item herramienta-custom-item" style="cursor:pointer">
            <div class="checklist-item-name">
                <input type="checkbox" name="<?= e($name) ?>" value="<?= e($cod) ?>" checked>
                <?= e(herramienta_nombre($cod)) ?>
                <button type="button" class="btn btn-sm btn-secondary herramienta-custom-remove" title="Quitar" aria-label="Quitar herramienta">&times;</button>
            </div>
        </label>
        <?php endforeach; ?>
    </div>
    <div class="input-group mt-2" style="max-width:420px">
        <input type="text" class="form-control" data-herramientas-custom-input maxlength="40" placeholder="Otra herramienta no listada…">
        <button type="button" class="btn btn-accent" data-herramientas-custom-add title="Agregar herramienta" aria-label="Agregar herramienta">+</button>
    </div>
    <small class="form-hint text-muted">Use el botón + si falta algún equipo que no aparece arriba.</small>
    <?php endif; ?>
</div>
