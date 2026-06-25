<?php
/** @var list<array{tipo: string, nombre?: string}> $servicios */
/** @var array<string, array{km?: mixed, meses?: mixed}> $intervalos */
/** @var list<string> $selectedServicios */
$servicios = $servicios ?? [];
$intervalos = $intervalos ?? [];
$selectedServicios = $selectedServicios ?? [];
$visible = !empty($visible);
$hayServicios = $servicios !== [];
$haySeleccionados = array_filter($selectedServicios, static fn ($s) => trim((string) $s) !== '') !== [];
?>
<div id="mantenimiento-intervalos-wrap" class="mantenimiento-intervalos-wrap card card--nested mt-2" <?= $visible ? '' : 'hidden' ?>>
    <div class="card-body">
        <h3 class="h4 mb-1">¿Cuándo toca el próximo servicio?</h3>
        <p class="text-muted mb-2">Indique en cuántos kilómetros o meses debe realizarse el siguiente servicio (al menos uno por servicio seleccionado).</p>

        <?php if (!$hayServicios): ?>
        <p class="alert alert-warning mb-0">
            No hay tipos de servicio registrados. Use «+ Agregar» arriba o en
            <a href="<?= url('catalogos/servicios') ?>">Catálogos → Servicios</a> para dar de alta uno.
        </p>
        <?php else: ?>
        <p id="mantenimiento-intervalos-hint" class="form-hint text-muted mb-2" <?= $haySeleccionados ? 'hidden' : '' ?>>
            Primero toque arriba las etiquetas de los servicios realizados; aquí aparecerán los campos de km y meses.
        </p>
        <div id="mantenimiento-intervalos-list" class="mantenimiento-intervalos-list">
            <?php foreach ($servicios as $s): ?>
            <?php
            $tipo = (string) ($s['tipo'] ?? '');
            $label = (string) ($s['nombre'] ?? mantenimiento_servicio_label($tipo));
            $vals = $intervalos[$tipo] ?? [];
            $kmVal = $vals['km'] ?? '';
            $mesesVal = $vals['meses'] ?? '';
            $isSelected = in_array($tipo, $selectedServicios, true);
            ?>
            <div class="mantenimiento-intervalo-row" data-intervalo-servicio="<?= e($tipo) ?>" <?= $isSelected ? '' : 'hidden' ?>>
                <strong class="mantenimiento-intervalo-label"><?= e($label) ?></strong>
                <div class="form-row mantenimiento-intervalo-fields">
                    <div class="form-group">
                        <label class="form-label">Próximo en (km)</label>
                        <input type="number" name="intervalos[<?= e($tipo) ?>][km]" class="form-control"
                               min="1" step="1" placeholder="Ej. 5000"
                               value="<?= e((string) $kmVal) ?>" data-intervalo-km
                               <?= $isSelected ? '' : 'disabled' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Próximo en (meses)</label>
                        <input type="number" name="intervalos[<?= e($tipo) ?>][meses]" class="form-control"
                               min="1" step="1" placeholder="Ej. 6"
                               value="<?= e((string) $mesesVal) ?>" data-intervalo-meses
                               <?= $isSelected ? '' : 'disabled' ?>>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
(function () {
    var wrap = document.getElementById('mantenimiento-intervalos-wrap');
    var picker = document.querySelector('[data-servicios-mantenimiento]');
    var tipoSelect = document.querySelector('[data-tipo-mantenimiento]');
    var hint = document.getElementById('mantenimiento-intervalos-hint');
    if (!wrap || !picker) return;

    function syncIntervalos() {
        var esPreventivo = !tipoSelect || tipoSelect.value === 'preventivo';
        wrap.hidden = !esPreventivo;
        var checks = picker.querySelectorAll('input[type="checkbox"][name="servicios[]"]');
        var algunoVisible = false;
        checks.forEach(function (cb) {
            var tipo = cb.value;
            var row = wrap.querySelector('[data-intervalo-servicio="' + tipo + '"]');
            if (!row) return;
            var show = esPreventivo && cb.checked;
            row.hidden = !show;
            row.querySelectorAll('input').forEach(function (inp) {
                inp.disabled = !show;
            });
            if (show) algunoVisible = true;
        });
        if (hint) hint.hidden = algunoVisible;
    }

    picker.addEventListener('change', syncIntervalos);
    picker.addEventListener('click', function () {
        window.setTimeout(syncIntervalos, 0);
    });
    if (tipoSelect) tipoSelect.addEventListener('change', syncIntervalos);
    syncIntervalos();

    var form = wrap.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (tipoSelect && tipoSelect.value !== 'preventivo') return;
            var invalid = [];
            wrap.querySelectorAll('.mantenimiento-intervalo-row:not([hidden])').forEach(function (row) {
                var km = row.querySelector('[data-intervalo-km]');
                var meses = row.querySelector('[data-intervalo-meses]');
                var kmVal = km && km.value !== '' ? parseInt(km.value, 10) : 0;
                var mesesVal = meses && meses.value !== '' ? parseInt(meses.value, 10) : 0;
                if (kmVal <= 0 && mesesVal <= 0) {
                    var label = row.querySelector('.mantenimiento-intervalo-label');
                    invalid.push(label ? label.textContent.trim() : 'servicio');
                }
            });
            if (invalid.length) {
                e.preventDefault();
                alert('Indique cuántos km o meses faltan para el próximo servicio de: ' + invalid.join(', '));
            }
        });
    }
})();
</script>
