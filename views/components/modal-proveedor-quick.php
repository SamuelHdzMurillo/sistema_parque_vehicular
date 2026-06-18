<?php
$tipo = $tipo ?? 'mantenimiento';
$contexto = $contexto ?? 'mantenimiento';
?>
<div class="modal-overlay" id="modal-proveedor-quick" data-proveedor-quick-modal aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-labelledby="modal-proveedor-quick-title">
        <div class="modal-header">
            <h3 id="modal-proveedor-quick-title" class="modal-title">Nuevo proveedor / taller</h3>
            <button type="button" class="modal-close" data-proveedor-quick-close aria-label="Cerrar">&times;</button>
        </div>
        <form data-proveedor-quick-form action="<?= e(url_path('proveedores/quick')) ?>" method="post" novalidate>
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="modal-body">
                <p class="card-header-hint mb-2">Se agregará al catálogo y quedará disponible en <?= e($contexto) ?>.</p>
                <div class="form-group">
                    <label class="form-label" for="modal-proveedor-razon">Razón social <span class="required">*</span></label>
                    <input type="text" id="modal-proveedor-razon" name="razon_social" class="form-control" required maxlength="200" placeholder="Ej. Taller Mecánico del Norte">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="modal-proveedor-rfc">RFC</label>
                        <input type="text" id="modal-proveedor-rfc" name="rfc" class="form-control" maxlength="13" placeholder="Opcional">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modal-proveedor-telefono">Teléfono</label>
                        <input type="text" id="modal-proveedor-telefono" name="telefono" class="form-control" maxlength="20" placeholder="Opcional">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="modal-proveedor-email">Email</label>
                    <input type="email" id="modal-proveedor-email" name="email" class="form-control" maxlength="150" placeholder="Opcional">
                </div>
                <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
                <input type="hidden" name="activo" value="1">
                <div class="alert alert-error" data-proveedor-quick-error hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-proveedor-quick-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" data-proveedor-quick-submit>Registrar</button>
            </div>
        </form>
    </div>
</div>
