<?php ?>
<div class="modal-overlay modal-overlay--stacked" id="modal-plantel-quick" data-plantel-quick-modal aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-labelledby="modal-plantel-quick-title">
        <div class="modal-header">
            <h3 id="modal-plantel-quick-title" class="modal-title">Nuevo plantel</h3>
            <button type="button" class="modal-close" data-plantel-quick-close aria-label="Cerrar">&times;</button>
        </div>
        <form data-plantel-quick-form action="<?= e(url_path('catalogos/planteles/quick')) ?>" method="post" novalidate>
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="modal-body">
                <p class="card-header-hint mb-2">Ej. <em>DG — Dirección General</em>, <em>LP — La Paz</em>.</p>
                <div class="form-group">
                    <label class="form-label" for="modal-plantel-clave">Clave <span class="required">*</span></label>
                    <input type="text" id="modal-plantel-clave" name="clave" class="form-control" required maxlength="20" placeholder="Ej. DG, LP, CAB">
                    <small class="form-hint text-muted">Se guardará en mayúsculas.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="modal-plantel-nombre">Nombre <span class="required">*</span></label>
                    <input type="text" id="modal-plantel-nombre" name="nombre" class="form-control" required maxlength="150" placeholder="Ej. Dirección General">
                </div>
                <input type="hidden" name="activo" value="1">
                <div class="alert alert-error" data-plantel-quick-error hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-plantel-quick-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" data-plantel-quick-submit>Registrar</button>
            </div>
        </form>
    </div>
</div>
