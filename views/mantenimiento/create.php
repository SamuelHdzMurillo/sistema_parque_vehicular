<?php
$pageTitle = 'Nuevo mantenimiento';
$vehiculos = $vehiculos ?? [];
$proveedores = $proveedores ?? [];
$responsables = $responsables ?? [];
$areas = $areas ?? [];
$planteles = $planteles ?? [];
$tipos = $tipos ?? [];
$servicios = $servicios ?? [];
$folioSugerido = (string) ($folio_sugerido ?? '');
$puedeAgregarArea = can('catalogos.create');
$preVehiculo = $_GET['vehiculo_id'] ?? old('vehiculo_id');
$preServicio = $_GET['servicio'] ?? old('servicio');
$oldServicios = old('servicios', []);
if (!is_array($oldServicios)) {
    $oldServicios = $oldServicios !== '' ? [(string) $oldServicios] : [];
}
if ($oldServicios === [] && $preServicio !== null && $preServicio !== '') {
    $oldServicios = [(string) $preServicio];
}
$responsableActual = old('responsable_id', auth_id());
$puedeAgregarResponsable = can('usuarios.create') || can('mantenimiento.create');
$puedeAgregarServicio = !empty($puede_agregar_servicio) || can('mantenimiento.create');
$returnToServicio = 'mantenimiento/create';
$oldIntervalos = old('intervalos', []);
if (!is_array($oldIntervalos)) {
    $oldIntervalos = [];
}
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('mantenimiento') ?>">Mantenimiento</a></li><li>/ Nuevo</li></ul>
        <h1 class="page-title">Registrar mantenimiento</h1>
    </div>
</div>
<div class="card">
    <form action="<?= url('mantenimiento') ?>" method="post" enctype="multipart/form-data" class="card-body mantenimiento-form">
        <?= csrf_field() ?>

        <section class="mantenimiento-form-section">
            <h2 class="mantenimiento-form-section-title">Datos generales</h2>
            <?php if ($folioSugerido !== ''): ?>
            <div class="form-group">
                <label class="form-label" for="folio">Folio de servicio</label>
                <input type="text" id="folio" name="folio" class="form-control"
                       pattern="MNT-\d{4}-\d+"
                       title="Formato: MNT-AAAA-NNN (ejemplo: MNT-2026-001)"
                       placeholder="<?= e($folioSugerido) ?> (automático si se deja vacío)"
                       value="<?= e((string) old('folio', '')) ?>">
                <small class="form-hint text-muted">Propuesto: <strong><?= e($folioSugerido) ?></strong>. Deje vacío para asignarlo automáticamente.</small>
            </div>
            <?php endif; ?>
            <div class="form-row form-row--2">
                <div class="form-group mb-0">
                    <label class="form-label" for="vehiculo_id">Vehículo <span class="required">*</span></label>
                    <select id="vehiculo_id" name="vehiculo_id" class="form-select" required data-km-source>
                        <option value="">Seleccione…</option>
                        <?php foreach ($vehiculos as $v): ?>
                        <option value="<?= (int) $v['id'] ?>" data-km="<?= (int) ($v['kilometraje_actual'] ?? 0) ?>" <?= (string) $preVehiculo === (string) $v['id'] ? 'selected' : '' ?>><?= e(catalogo_vehiculo_label($v)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="tipo">Tipo <span class="required">*</span></label>
                    <select id="tipo" name="tipo" class="form-select" required data-tipo-mantenimiento>
                        <?php foreach ($tipos as $t): ?>
                        <option value="<?= e($t) ?>" <?= old('tipo', 'preventivo') === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>

        <section class="mantenimiento-form-section">
            <h2 class="mantenimiento-form-section-title">Servicios realizados</h2>
            <div class="form-group mb-0" id="grupo-servicio">
                <label class="form-label">Seleccione los servicios <span class="required">*</span></label>
                <?php App\Core\View::component('mantenimiento-servicios-picker', [
                    'servicios' => $servicios,
                    'selected' => $oldServicios,
                    'required' => true,
                    'puedeAgregar' => $puedeAgregarServicio,
                    'returnTo' => $returnToServicio,
                    'openAgregar' => $servicios === [],
                    'formId' => 'mantenimiento-servicio-form',
                ]); ?>
            </div>
            <?php App\Core\View::component('mantenimiento-intervalos', [
                'servicios' => $servicios,
                'intervalos' => $oldIntervalos,
                'selectedServicios' => $oldServicios,
                'visible' => old('tipo', 'preventivo') === 'preventivo',
            ]); ?>
        </section>

        <section class="mantenimiento-form-section">
            <h2 class="mantenimiento-form-section-title">Registro del servicio</h2>
            <div class="form-row form-row--2">
                <div class="form-group">
                    <label class="form-label" for="fecha">Fecha <span class="required">*</span></label>
                    <input type="date" id="fecha" name="fecha" class="form-control" required value="<?= e((string) old('fecha', date('Y-m-d'))) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="kilometraje">Kilometraje <span class="required">*</span></label>
                    <input type="number" id="kilometraje" name="kilometraje" class="form-control" required min="0" data-km-target value="<?= e((string) old('kilometraje')) ?>">
                    <small class="form-hint text-muted" data-km-hint>Seleccione un vehículo para ver el kilometraje actual.</small>
                </div>
            </div>
            <div class="form-historico-note mb-2">
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="es_historico" value="1" data-km-historic-toggle <?= old('es_historico') ? 'checked' : '' ?>>
                        Mantenimiento anterior al kilometraje actual
                    </label>
                    <small class="form-hint text-muted">Permite registrar un servicio con kilometraje menor al actual del vehículo.</small>
                </div>
            </div>
            <div class="form-group mb-0">
                <label class="form-label" for="descripcion">Descripción del servicio <span class="required">*</span></label>
                <textarea id="descripcion" name="descripcion" class="form-textarea" rows="4" required><?= e((string) old('descripcion')) ?></textarea>
            </div>
        </section>

        <section class="mantenimiento-form-section">
            <h2 class="mantenimiento-form-section-title">Proveedor y costos</h2>
            <div class="form-row form-row--3">
                <div class="form-group">
                    <label class="form-label" for="proveedor_id">Proveedor / taller</label>
                    <div class="input-group">
                        <select id="proveedor_id" name="proveedor_id" class="form-select" data-proveedor-select>
                            <option value="">— Sin proveedor —</option>
                            <?php foreach ($proveedores as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"
                                data-rfc="<?= e($p['rfc'] ?? '') ?>"
                                data-telefono="<?= e($p['telefono'] ?? '') ?>"
                                data-email="<?= e($p['email'] ?? '') ?>"
                                data-direccion="<?= e($p['direccion'] ?? '') ?>"
                                <?= (string) old('proveedor_id') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['razon_social']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (can('proveedores.create')): ?>
                        <button type="button" class="btn btn-accent" data-proveedor-quick-open title="Agregar proveedor" aria-label="Agregar proveedor">+</button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="costo">Costo estimado</label>
                    <input type="number" id="costo" name="costo" class="form-control" step="0.01" min="0" value="<?= e((string) old('costo', '0')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="responsable_id">Responsable</label>
                    <div class="input-group">
                        <select id="responsable_id" name="responsable_id" class="form-select" data-responsable-select>
                            <?php foreach ($responsables as $u): ?>
                            <option value="<?= (int) $u['id'] ?>" <?= (string) $responsableActual === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['nombre_completo'] ?? $u['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($puedeAgregarResponsable): ?>
                        <button type="button" class="btn btn-accent" data-responsable-quick-open title="Agregar responsable" aria-label="Agregar responsable">+</button>
                        <?php endif; ?>
                    </div>
                </div>
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
        </section>

        <section class="mantenimiento-form-section">
            <fieldset class="form-fieldset">
                <legend>Factura del proveedor</legend>
                <div class="form-row form-row--3">
                    <div class="form-group">
                        <label class="form-label" for="factura_folio">Folio / serie de factura</label>
                        <input type="text" id="factura_folio" name="factura_folio" class="form-control" value="<?= e((string) old('factura_folio')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="factura_fecha">Fecha de factura</label>
                        <input type="date" id="factura_fecha" name="factura_fecha" class="form-control" value="<?= e((string) old('factura_fecha')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="factura_uuid">Folio fiscal (UUID)</label>
                        <input type="text" id="factura_uuid" name="factura_uuid" class="form-control" maxlength="40" value="<?= e((string) old('factura_uuid')) ?>">
                    </div>
                </div>
                <div class="form-row form-row--3">
                    <div class="form-group">
                        <label class="form-label" for="factura_subtotal">Subtotal</label>
                        <input type="number" id="factura_subtotal" name="factura_subtotal" class="form-control" step="0.01" min="0" value="<?= e((string) old('factura_subtotal')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="factura_iva">IVA</label>
                        <input type="number" id="factura_iva" name="factura_iva" class="form-control" step="0.01" min="0" value="<?= e((string) old('factura_iva')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="factura_total">Total</label>
                        <input type="number" id="factura_total" name="factura_total" class="form-control" step="0.01" min="0" value="<?= e((string) old('factura_total')) ?>">
                    </div>
                </div>
                <div class="form-row form-row--2">
                    <div class="form-group mb-0">
                        <label class="form-label" for="archivo_factura">Archivo de factura</label>
                        <input type="file" id="archivo_factura" name="archivo_factura" class="form-control" accept="application/pdf,image/jpeg,image/png">
                        <p class="form-hint">Suba la factura en JPG o PNG para que aparezca al imprimir el documento. También acepta PDF (se guarda aparte).</p>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" for="archivo_xml">Archivo XML (CFDI)</label>
                        <input type="file" id="archivo_xml" name="archivo_xml" class="form-control" accept=".xml,application/xml,text/xml">
                    </div>
                </div>
            </fieldset>
        </section>

        <div class="d-flex gap-1 mt-2">
            <button type="submit" class="btn btn-primary">Registrar</button>
            <a href="<?= url('formatos/mantenimiento') ?>" class="btn btn-secondary" target="_blank">Formato PDF en blanco</a>
            <a href="<?= url('mantenimiento') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
    <?php if ($puedeAgregarServicio): ?>
    <form id="mantenimiento-servicio-form" action="<?= url('mantenimiento/servicios') ?>" method="post" class="sr-only" aria-hidden="true" tabindex="-1">
        <?= csrf_field() ?>
        <input type="hidden" name="return_to" value="<?= e($returnToServicio) ?>">
    </form>
    <?php endif; ?>
</div>

<?php if (can('proveedores.create')): ?>
<?php App\Core\View::component('modal-proveedor-quick', ['tipo' => 'mantenimiento', 'contexto' => 'mantenimiento']); ?>
<?php endif; ?>
<?php if ($puedeAgregarResponsable): ?>
<?php App\Core\View::component('modal-responsable-quick', ['areas' => $areas]); ?>
<?php if ($puedeAgregarArea): ?>
<?php App\Core\View::component('modal-area-quick', ['planteles' => $planteles]); ?>
<?php App\Core\View::component('modal-plantel-quick'); ?>
<?php endif; ?>
<?php endif; ?>

<script>
(function () {
    var tipo = document.querySelector('[data-tipo-mantenimiento]');
    var grupo = document.getElementById('grupo-servicio');
    var checks = document.querySelectorAll('[data-servicios-mantenimiento] input[type="checkbox"]');
    function syncServicio() {
        if (!tipo || !grupo) return;
        var esPreventivo = tipo.value === 'preventivo';
        grupo.style.display = esPreventivo ? '' : 'none';
        checks.forEach(function (cb) {
            cb.required = false;
            if (!esPreventivo) {
                cb.checked = false;
                var chip = cb.parentElement && cb.parentElement.querySelector('.servicios-picker-chip');
                if (chip) chip.classList.remove('is-selected');
            }
        });
    }
    if (tipo) tipo.addEventListener('change', syncServicio);
    syncServicio();
    var form = grupo ? grupo.closest('form') : null;
    if (form) {
        form.addEventListener('submit', function (e) {
            if (tipo && tipo.value !== 'preventivo') return;
            var alguno = Array.prototype.some.call(checks, function (cb) { return cb.checked; });
            if (!alguno) {
                e.preventDefault();
                alert('Seleccione al menos un servicio tocando las etiquetas.');
            }
        });
    }
})();
</script>
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
