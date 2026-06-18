<?php
$areas = $areas ?? [];
?>
<div class="modal-overlay" id="modal-conductor-quick" data-conductor-quick-modal aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-labelledby="modal-conductor-quick-title">
        <div class="modal-header">
            <h3 id="modal-conductor-quick-title" class="modal-title">Nuevo conductor</h3>
            <button type="button" class="modal-close" data-conductor-quick-close aria-label="Cerrar">&times;</button>
        </div>
        <form data-conductor-quick-form action="<?= e(url_path('catalogos/conductores/quick')) ?>" method="post" novalidate>
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="modal-body">
                <p class="card-header-hint mb-2">Se agregará al catálogo y quedará disponible en comisiones.</p>
                <div class="form-group">
                    <label class="form-label" for="modal-conductor-nombre">Nombre completo <span class="required">*</span></label>
                    <input type="text" id="modal-conductor-nombre" name="nombre" class="form-control" required maxlength="200" placeholder="Ej. Juan Pérez García">
                </div>
                <div class="form-group">
                    <label class="form-label" for="modal-conductor-area">Área <span class="required">*</span></label>
                    <select id="modal-conductor-area" name="area_id" class="form-select" required data-conductor-area-select>
                        <option value="">Seleccione…</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= (int) $a['id'] ?>"><?= e($a['label'] ?? catalogo_area_label($a)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="modal-conductor-telefono">Teléfono <span class="required">*</span></label>
                    <input type="text" id="modal-conductor-telefono" name="telefono" class="form-control" required maxlength="20" placeholder="Ej. 6121234567">
                </div>
                <input type="hidden" name="activo" value="1">
                <div class="alert alert-error" data-conductor-quick-error hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-conductor-quick-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" data-conductor-quick-submit>Registrar</button>
            </div>
        </form>
    </div>
</div>
