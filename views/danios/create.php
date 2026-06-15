<?php
$pageTitle = 'Reportar daño';
$vehiculos = $vehiculos ?? [];
$preVehiculo = $_GET['vehiculo_id'] ?? old('vehiculo_id');
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('danios') ?>">Daños</a></li><li>/ Nuevo</li></ul>
        <h1 class="page-title">Reportar daño</h1>
    </div>
</div>
<div class="card">
    <form action="<?= url('danios') ?>" method="post" enctype="multipart/form-data" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="vehiculo_id">Vehículo <span class="required">*</span></label>
                <select id="vehiculo_id" name="vehiculo_id" class="form-select" required>
                    <option value="">Seleccione…</option>
                    <?php foreach ($vehiculos as $v): ?>
                    <option value="<?= (int) $v['id'] ?>" <?= (string) $preVehiculo === (string) $v['id'] ? 'selected' : '' ?>>
                        <?= e($v['numero_economico'] . ' — ' . ($v['placas'] ?? '')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="tipo_dano">Tipo de daño <span class="required">*</span></label>
                <select id="tipo_dano" name="tipo_dano" class="form-select" required>
                    <?php foreach (['golpe','rayon','cristal','mecanico','electrico','otro'] as $t): ?>
                    <option value="<?= e($t) ?>" <?= old('tipo_dano') === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="ubicacion">Ubicación <span class="required">*</span></label>
                <input type="text" id="ubicacion" name="ubicacion" class="form-control" required placeholder="Ej. Puerta trasera izquierda"
                       value="<?= e((string) old('ubicacion')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label" for="descripcion">Descripción <span class="required">*</span></label>
            <textarea id="descripcion" name="descripcion" class="form-textarea" required><?= e((string) old('descripcion')) ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label" for="foto">Fotografía del daño</label>
            <input type="file" id="foto" name="foto" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Enviar reporte</button>
            <a href="<?= url('danios') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
