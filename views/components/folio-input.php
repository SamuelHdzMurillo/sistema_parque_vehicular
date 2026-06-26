<?php
$id = $id ?? 'folio';
$name = $name ?? 'folio';
$label = $label ?? 'Folio';
$tipo = strtoupper((string) ($tipo ?? 'XXX'));
$pad = max(1, (int) ($pad ?? 4));
$sugerido = trim((string) ($sugerido ?? ''));
$hint = $hint ?? 'Modifique solo el número. Los siguientes folios continuarán a partir de este.';
$required = !empty($required);

$oldVal = trim((string) old($name, ''));
$source = $oldVal !== '' ? $oldVal : $sugerido;
$partes = folio_partes($source, $pad);
$prefix = $partes['prefix'] ?? ($tipo . '-' . date('Y') . '-');
$numPadded = $partes['num_padded'] ?? str_pad('1', $pad, '0', STR_PAD_LEFT);
$full = $partes['full'] ?? ($prefix . $numPadded);
?>
<div class="form-group">
    <label class="form-label" for="<?= e($id) ?>_seq"><?= e($label) ?><?= $required ? ' <span class="required">*</span>' : '' ?></label>
    <div class="folio-input input-group" data-folio-input data-folio-pad="<?= (int) $pad ?>" data-folio-default-seq="<?= e($numPadded) ?>">
        <span class="folio-input__prefix form-control" data-folio-prefix aria-hidden="true"><?= e($prefix) ?></span>
        <input type="text"
               id="<?= e($id) ?>_seq"
               class="folio-input__seq form-control"
               data-folio-seq
               inputmode="numeric"
               pattern="\d+"
               autocomplete="off"
               aria-label="Número consecutivo del folio"
               value="<?= e($numPadded) ?>"
               <?= $required ? 'required' : '' ?>>
        <input type="hidden"
               id="<?= e($id) ?>"
               name="<?= e($name) ?>"
               data-folio-value
               value="<?= e($full) ?>">
    </div>
    <?php if ($hint !== ''): ?>
    <small class="form-hint text-muted"><?= e((string) $hint) ?></small>
    <?php endif; ?>
</div>
