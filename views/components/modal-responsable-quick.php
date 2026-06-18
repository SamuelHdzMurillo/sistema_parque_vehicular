<?php
$areas = $areas ?? [];
?>
<div class="modal-overlay" id="modal-responsable-quick" data-responsable-quick-modal aria-hidden="true">
    <div class="modal-dialog" role="dialog" aria-labelledby="modal-responsable-quick-title">
        <div class="modal-header">
            <h3 id="modal-responsable-quick-title" class="modal-title">Nuevo responsable</h3>
            <button type="button" class="modal-close" data-responsable-quick-close aria-label="Cerrar">&times;</button>
        </div>
        <form data-responsable-quick-form action="<?= e(url_path('usuarios/quick')) ?>" method="post" novalidate>
            <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
            <div class="modal-body">
                <p class="card-header-hint mb-2">Se registrará como usuario con rol de responsable de vehículo y quedará disponible en el formulario.</p>
                <div class="form-group">
                    <label class="form-label" for="modal-responsable-nombre">Nombre(s) <span class="required">*</span></label>
                    <input type="text" id="modal-responsable-nombre" name="nombre" class="form-control" required maxlength="100" placeholder="Ej. Juan">
                </div>
                <div class="form-group">
                    <label class="form-label" for="modal-responsable-apellido">Apellido paterno <span class="required">*</span></label>
                    <input type="text" id="modal-responsable-apellido" name="apellido_paterno" class="form-control" required maxlength="100" placeholder="Ej. Pérez">
                </div>
                <div class="form-group">
                    <label class="form-label" for="modal-responsable-email">Correo electrónico <span class="required">*</span></label>
                    <input type="email" id="modal-responsable-email" name="email" class="form-control" required maxlength="150" placeholder="Ej. juan.perez@ejemplo.com">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="modal-responsable-telefono">Teléfono</label>
                        <input type="text" id="modal-responsable-telefono" name="telefono" class="form-control" maxlength="20" placeholder="Opcional">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="modal-responsable-area">Área</label>
                        <div class="input-group">
                            <select id="modal-responsable-area" name="area_id" class="form-select" data-responsable-area-select>
                                <option value="">— Sin área —</option>
                                <?php foreach ($areas as $a): ?>
                                <option value="<?= (int) $a['id'] ?>"><?= e($a['label'] ?? catalogo_area_label($a)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (can('catalogos.create')): ?>
                            <button type="button" class="btn btn-accent" data-area-quick-open title="Agregar área" aria-label="Agregar área">+</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="alert alert-error" data-responsable-quick-error hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-responsable-quick-close>Cancelar</button>
                <button type="submit" class="btn btn-primary" data-responsable-quick-submit>Registrar</button>
            </div>
        </form>
    </div>
</div>
