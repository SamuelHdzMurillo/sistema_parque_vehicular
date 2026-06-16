<?php
$pageTitle = 'Registrar carga';
$vehiculos = $vehiculos ?? [];
$proveedores = $proveedores ?? [];
$preVehiculo = $_GET['vehiculo_id'] ?? old('vehiculo_id');
$kilometrajeInicial = old('kilometraje');
if ($kilometrajeInicial === null || $kilometrajeInicial === '') {
    foreach ($vehiculos as $v) {
        if ((string) $preVehiculo === (string) $v['id']) {
            $kilometrajeInicial = (int) ($v['kilometraje_actual'] ?? 0);
            break;
        }
    }
}
$kilometrajeInicial = $kilometrajeInicial ?? '';
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('combustible') ?>">Combustible</a></li><li>/ Nueva carga</li></ul>
        <h1 class="page-title">Registrar carga de combustible</h1>
    </div>
</div>
<div class="card">
    <form action="<?= url('combustible') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="vehiculo_id">Vehículo <span class="required">*</span></label>
                <select id="vehiculo_id" name="vehiculo_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($vehiculos as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" data-km="<?= (int) ($v['kilometraje_actual'] ?? 0) ?>" <?= (string) $preVehiculo === (string) $v['id'] ? 'selected' : '' ?>><?= e($v['numero_economico']) ?> (<?= number_format((int) ($v['kilometraje_actual'] ?? 0)) ?> km)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha <span class="required">*</span></label>
                <input type="date" id="fecha" name="fecha" class="form-control" required value="<?= e((string) old('fecha', date('Y-m-d'))) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="kilometraje">Kilometraje al cargar <span class="required">*</span></label>
                <input type="number" id="kilometraje" name="kilometraje" class="form-control" required min="0" value="<?= e((string) $kilometrajeInicial) ?>">
                <small id="kilometraje-hint" class="text-muted">Debe ser igual o mayor al kilometraje actual del vehículo.</small>
            </div>
            <div class="form-group">
                <label class="form-label" for="litros">Litros <span class="required">*</span></label>
                <input type="number" id="litros" name="litros" class="form-control" required step="0.01" min="0" value="<?= e((string) old('litros')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="importe">Importe <span class="required">*</span></label>
                <input type="number" id="importe" name="importe" class="form-control" required step="0.01" min="0" value="<?= e((string) old('importe')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="proveedor_id">Estación / Proveedor</label>
                <select id="proveedor_id" name="proveedor_id" class="form-select">
                    <option value="">— Opcional —</option>
                    <?php foreach ($proveedores as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= e($p['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="folio_ticket">Folio ticket</label>
                <input type="text" id="folio_ticket" name="folio_ticket" class="form-control" value="<?= e((string) old('folio_ticket')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-textarea"><?= e((string) old('observaciones')) ?></textarea>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Registrar carga</button>
            <a href="<?= url('formatos/combustible') ?>" class="btn btn-secondary" target="_blank">Formato PDF en blanco</a>
            <a href="<?= url('combustible') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<script>
(function () {
    var select = document.getElementById('vehiculo_id');
    var input = document.getElementById('kilometraje');
    var hint = document.getElementById('kilometraje-hint');
    if (!select || !input) return;

    function syncKilometraje() {
        var option = select.options[select.selectedIndex];
        if (!option || !option.value) return;
        var km = option.getAttribute('data-km') || '0';
        input.min = km;
        if (hint) {
            hint.textContent = 'Kilometraje actual del vehículo: ' + Number(km).toLocaleString('es-MX') + ' km. Debe ser igual o mayor.';
        }
        if (!input.value || Number(input.value) < Number(km)) {
            input.value = km;
        }
    }

    select.addEventListener('change', syncKilometraje);
    syncKilometraje();
})();
</script>
