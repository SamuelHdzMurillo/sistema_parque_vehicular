<?php
$pageTitle = 'Actualizar documento';
$documento = $documento ?? [];
$vehiculos = $vehiculos ?? [];
$d = array_merge($documento, array_intersect_key($_SESSION['_old'] ?? [], array_flip([
    'titulo', 'numero_documento', 'fecha_emision', 'fecha_vencimiento',
])));
$archivoUrl = !empty($d['archivo_ruta'])
    ? url('storage/uploads/' . ltrim((string) $d['archivo_ruta'], '/'))
    : '';
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('documentos') ?>">Documentos</a></li>
            <li>/ Actualizar</li>
        </ul>
        <h1 class="page-title">Actualizar documento</h1>
        <p class="page-subtitle">
            <?= e(ucfirst(str_replace('_', ' ', (string) ($d['tipo'] ?? '')))) ?>
            · Versión <?= e((string) ($d['version'] ?? '1')) ?>
            <?php if (!empty($d['numero_economico'])): ?>
            · Vehículo <?= e((string) $d['numero_economico']) ?>
            <?php endif; ?>
        </p>
    </div>
</div>
<div class="card">
    <form action="<?= url('documentos/' . (int) $d['id']) ?>" method="post" enctype="multipart/form-data" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tipo</label>
                <input type="text" class="form-control" readonly value="<?= e(ucfirst(str_replace('_', ' ', (string) ($d['tipo'] ?? '')))) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Vehículo</label>
                <input type="text" class="form-control" readonly value="<?= e((string) ($d['numero_economico'] ?? '—')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="titulo">Título <span class="required">*</span></label>
                <input type="text" id="titulo" name="titulo" class="form-control" required value="<?= e((string) ($d['titulo'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="numero_documento">No. documento</label>
                <input type="text" id="numero_documento" name="numero_documento" class="form-control" value="<?= e((string) ($d['numero_documento'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha_emision">Fecha emisión</label>
                <input type="date" id="fecha_emision" name="fecha_emision" class="form-control" value="<?= e((string) ($d['fecha_emision'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="fecha_vencimiento">Fecha vencimiento</label>
                <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" class="form-control" value="<?= e((string) ($d['fecha_vencimiento'] ?? '')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Archivo actual</label>
            <?php if ($archivoUrl !== ''): ?>
            <div class="d-flex gap-1 align-items-center">
                <a href="<?= e($archivoUrl) ?>" class="btn btn-sm btn-info" target="_blank" rel="noopener">Ver archivo</a>
                <a href="<?= url('documentos/' . (int) $d['id'] . '/download') ?>" class="btn btn-sm btn-secondary">Descargar</a>
            </div>
            <?php else: ?>
            <p class="text-muted mb-0">Sin archivo adjunto</p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label class="form-label" for="archivo">Nuevo archivo (opcional)</label>
            <input type="file" id="archivo" name="archivo" class="form-control" accept=".pdf,.xml,.jpg,.jpeg,.png">
            <p class="form-hint">Si sube un archivo nuevo, se creará la versión <?= (int) ($d['version'] ?? 1) + 1 ?> y el documento anterior quedará en el historial.</p>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="<?= url('documentos') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
