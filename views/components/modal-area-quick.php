<?php
$planteles = $planteles ?? [];
?>
<div class="modal-overlay" id="modal-area-quick" data-area-quick-modal aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-labelledby="modal-area-quick-title">
        <div class="modal-header">
            <h3 id="modal-area-quick-title" class="modal-title">Nueva área solicitante</h3>
            <button type="button" class="modal-close" data-area-quick-close aria-label="Cerrar">&times;</button>
        </div>
        <form data-area-quick-form action="<?= e(url_path('catalogos/areas/quick')) ?>" method="post" novalidate>
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="modal-body">
                <p class="card-header-hint mb-2">Se mostrará en comisiones como <em>Nombre - Clave del plantel</em>.</p>
                <div class="form-group">
                    <label class="form-label" for="modal-area-clave">Clave <span class="required">*</span></label>
                    <input type="text" id="modal-area-clave" name="clave" class="form-control" required maxlength="20" placeholder="Ej. JUR">
                    <small class="form-hint text-muted">Se guardará en mayúsculas.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="modal-area-nombre">Nombre del área <span class="required">*</span></label>
                    <input type="text" id="modal-area-nombre" name="nombre" class="form-control" required maxlength="150" placeholder="Ej. Jurídico">
                </div>
                <div class="form-group">
                    <label class="form-label" for="modal-area-plantel">Plantel <span class="required">*</span></label>
                    <div class="input-group">
                        <select id="modal-area-plantel" name="plantel_id" class="form-select" required data-plantel-select>
                            <option value="">Seleccione…</option>
                            <?php foreach ($planteles as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= e($p['clave'] . ' — ' . $p['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (can('catalogos.create')): ?>
                        <button type="button" class="btn btn-accent" data-plantel-quick-open title="Agregar plantel" aria-label="Agregar plantel">+</button>
                        <?php endif; ?>
                    </div>
                </div>
                <input type="hidden" name="activo" value="1">
                <div class="alert alert-error" data-area-quick-error hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-area-quick-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" data-area-quick-submit>Registrar</button>
            </div>
        </form>
    </div>
</div>
