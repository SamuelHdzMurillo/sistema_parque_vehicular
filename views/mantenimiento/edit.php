<?php
$pageTitle = 'Editar mantenimiento';
$m = $mantenimiento ?? [];
$vehiculos = $vehiculos ?? [];
$proveedores = $proveedores ?? [];
$responsables = $responsables ?? [];
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
    <form action="<?= url('mantenimiento/' . $m['id']) ?>" method="post" class="card-body">
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
                <input type="number" id="kilometraje" name="kilometraje" class="form-control" value="<?= e((string) ($m['kilometraje'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="proveedor_id">Proveedor</label>
                <select id="proveedor_id" name="proveedor_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($proveedores as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= (int) ($m['proveedor_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['razon_social']) ?></option>
                    <?php endforeach; ?>
                </select>
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
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('mantenimiento/' . $m['id']) ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
