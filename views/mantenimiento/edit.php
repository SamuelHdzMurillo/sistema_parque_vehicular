<?php
$pageTitle = 'Editar mantenimiento';
$m = $mantenimiento ?? [];
$vehiculos = $vehiculos ?? [];
$proveedores = $proveedores ?? [];
$responsables = $responsables ?? [];
$areas = $areas ?? [];
$tipos = $tipos ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('mantenimiento') ?>">Mantenimiento</a></li>
            <li><a href="<?= url('mantenimiento/' . $m['id']) ?>"><?= e($m['folio']) ?></a></li>
            <li>/ Editar</li>
        </ul>
        <h1 class="page-title">Editar <?= e($m['folio']) ?></h1>
    </div>
</div>
<div class="card">
    <form action="<?= url('mantenimiento/' . $m['id']) ?>" method="post" enctype="multipart/form-data" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="tipo">Tipo</label>
                <select id="tipo" name="tipo" class="form-select" required>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= e($t) ?>" <?= ($m['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha">Fecha</label>
                <input type="date" id="fecha" name="fecha" class="form-control" value="<?= e($m['fecha'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="kilometraje">Kilometraje</label>
                <input type="number" id="kilometraje" name="kilometraje" class="form-control" value="<?= e((string) old('kilometraje', (string) ($m['kilometraje'] ?? ''))) ?>" required min="0">
                <small class="form-hint text-muted" data-km-hint data-km-value="<?= (int) ($m['kilometraje_actual'] ?? 0) ?>" <?= !empty($m['es_historico']) ? 'data-km-historic' : '' ?>></small>
            </div>
            <div class="form-group">
                <label class="form-label">&nbsp;</label>
                <label class="form-check">
                    <input type="checkbox" name="es_historico" value="1" data-km-historic-toggle <?= !empty(old('es_historico', $m['es_historico'] ?? 0)) ? 'checked' : '' ?>>
                    Mantenimiento anterior al kilometraje actual
                </label>
                <small class="form-hint text-muted">Registro histórico: no modifica el kilometraje actual del vehículo.</small>
            </div>
            <div class="form-group">
                <label class="form-label" for="proveedor_id">Proveedor / taller</label>
                <div class="input-group">
                    <select id="proveedor_id" name="proveedor_id" class="form-select" data-proveedor-select>
                        <option value="">—</option>
                        <?php foreach ($proveedores as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"
                            data-rfc="<?= e($p['rfc'] ?? '') ?>"
                            data-telefono="<?= e($p['telefono'] ?? '') ?>"
                            data-email="<?= e($p['email'] ?? '') ?>"
                            data-direccion="<?= e($p['direccion'] ?? '') ?>"
                            <?= (int) ($m['proveedor_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['razon_social']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (can('proveedores.create')): ?>
                    <button type="button" class="btn btn-accent" data-proveedor-quick-open title="Agregar proveedor" aria-label="Agregar proveedor">+</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="costo">Costo</label>
                <input type="number" id="costo" name="costo" class="form-control" step="0.01" value="<?= e((string) ($m['costo'] ?? '0')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" class="form-textarea" required><?= e($m['descripcion'] ?? '') ?></textarea>
        </div>

        <div id="proveedor-datos" class="alert alert-info" style="display:none;">
            <strong>Datos del proveedor</strong>
            <div class="meta-grid mt-1">
                <div class="meta-item"><label>RFC</label><span data-campo="rfc">—</span></div>
                <div class="meta-item"><label>Teléfono</label><span data-campo="telefono">—</span></div>
                <div class="meta-item"><label>Email</label><span data-campo="email">—</span></div>
                <div class="meta-item"><label>Dirección</label><span data-campo="direccion">—</span></div>
            </div>
        </div>

        <fieldset class="form-fieldset mt-2">
            <legend>Factura del proveedor</legend>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="factura_folio">Folio / serie de factura</label>
                    <input type="text" id="factura_folio" name="factura_folio" class="form-control" value="<?= e((string) ($m['factura_folio'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="factura_fecha">Fecha de factura</label>
                    <input type="date" id="factura_fecha" name="factura_fecha" class="form-control" value="<?= e((string) ($m['factura_fecha'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="factura_uuid">Folio fiscal (UUID)</label>
                    <input type="text" id="factura_uuid" name="factura_uuid" class="form-control" maxlength="40" value="<?= e((string) ($m['factura_uuid'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="factura_subtotal">Subtotal</label>
                    <input type="number" id="factura_subtotal" name="factura_subtotal" class="form-control" step="0.01" min="0" value="<?= e((string) ($m['factura_subtotal'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="factura_iva">IVA</label>
                    <input type="number" id="factura_iva" name="factura_iva" class="form-control" step="0.01" min="0" value="<?= e((string) ($m['factura_iva'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="factura_total">Total</label>
                    <input type="number" id="factura_total" name="factura_total" class="form-control" step="0.01" min="0" value="<?= e((string) ($m['factura_total'] ?? '')) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="archivo_factura">Archivo de factura</label>
                    <input type="file" id="archivo_factura" name="archivo_factura" class="form-control" accept="application/pdf,image/jpeg,image/png">
                    <p class="form-hint">Use JPG o PNG para que la factura salga al imprimir el documento.</p>
                    <?php if (!empty($m['factura_ruta'])): ?>
                    <p class="form-hint">Actual: <a href="<?= url('storage/uploads/' . ltrim((string) $m['factura_ruta'], '/')) ?>" target="_blank">ver factura</a> · subir un archivo lo reemplaza.</p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label class="form-label" for="archivo_xml">Archivo XML (CFDI)</label>
                    <input type="file" id="archivo_xml" name="archivo_xml" class="form-control" accept=".xml,application/xml,text/xml">
                    <?php if (!empty($m['xml_ruta'])): ?>
                    <p class="form-hint">Actual: <a href="<?= url('storage/uploads/' . ltrim((string) $m['xml_ruta'], '/')) ?>" target="_blank">ver XML</a> · subir un archivo lo reemplaza.</p>
                    <?php endif; ?>
                </div>
            </div>
        </fieldset>

        <div class="d-flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('mantenimiento/' . $m['id']) ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php if (can('proveedores.create')): ?>
<?php App\Core\View::component('modal-proveedor-quick', ['tipo' => 'mantenimiento', 'contexto' => 'mantenimiento']); ?>
<?php endif; ?>

<script>
(function () {
    var select = document.querySelector('[data-proveedor-select]');
    var box = document.getElementById('proveedor-datos');
    if (!select || !box) { return; }
    function pintar() {
        var opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) { box.style.display = 'none'; return; }
        ['rfc', 'telefono', 'email', 'direccion'].forEach(function (campo) {
            var span = box.querySelector('[data-campo="' + campo + '"]');
            if (span) { span.textContent = opt.getAttribute('data-' + campo) || '—'; }
        });
        box.style.display = '';
    }
    select.addEventListener('change', pintar);
    pintar();
})();
</script>
