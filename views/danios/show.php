<?php
$pageTitle = 'Daño #' . ($danio['id'] ?? '');
$d = $danio ?? [];
$estados = ['reportado','en_evaluacion','en_reparacion','reparado','cerrado_sin_accion'];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('danios') ?>">Daños</a></li><li>/ #<?= (int) ($d['id'] ?? 0) ?></li></ul>
        <h1 class="page-title">Daño en <?= e($d['numero_economico'] ?? '') ?></h1>
        <p class="page-subtitle"><span class="badge badge-warning"><?= e(str_replace('_', ' ', $d['estado'] ?? '')) ?></span></p>
    </div>
    <div class="page-actions">
        <a href="<?= url('formatos/danio/' . $d['id']) ?>" class="btn btn-secondary" target="_blank">Descargar PDF / Imprimir</a>
    </div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <div class="meta-grid">
            <div class="meta-item"><label>Vehículo</label><span><?= e($d['numero_economico'] ?? '') ?> (<?= e($d['placas'] ?? '') ?>)</span></div>
            <div class="meta-item"><label>Tipo</label><span><?= e($d['tipo_dano'] ?? '') ?></span></div>
            <div class="meta-item"><label>Ubicación</label><span><?= e($d['ubicacion'] ?? '') ?></span></div>
            <div class="meta-item"><label>Reportado</label><span><?= format_datetime($d['created_at'] ?? null) ?></span></div>
        </div>
        <p class="mt-2"><strong>Descripción:</strong> <?= e($d['descripcion'] ?? '') ?></p>
    </div>
</div>

<?php $puedeEditar = can('danios.update'); ?>
<div class="card mb-2">
    <div class="card-header"><h3>Fotografías<?= !empty($fotos) ? ' (' . count($fotos) . ')' : '' ?></h3></div>
    <div class="card-body">
        <?php if ($puedeEditar): ?>
        <form action="<?= url('danios/' . $d['id'] . '/fotos') ?>" method="post" enctype="multipart/form-data" class="danio-fotos-upload">
            <?= csrf_field() ?>
            <div class="form-group mb-0">
                <label class="form-label" for="fotos">Agregar fotografías</label>
                <input type="file" id="fotos" name="fotos[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required>
                <p class="form-hint">Seleccione una o varias imágenes (Ctrl o Shift para elegir varias).</p>
            </div>
            <button type="submit" class="btn btn-secondary">Subir</button>
        </form>
        <?php endif; ?>

        <?php if (!empty($fotos)): ?>
        <div class="danio-fotos-grid" data-lightbox-gallery>
            <?php foreach ($fotos as $f): ?>
            <?php $fUrl = url('storage/uploads/' . ltrim((string) $f['ruta'], '/')); ?>
            <div class="danio-foto-card">
                <a href="<?= e($fUrl) ?>" class="danio-foto-link" data-lightbox>
                    <img src="<?= e($fUrl) ?>" alt="Fotografía del daño" loading="lazy">
                </a>
                <?php if ($puedeEditar): ?>
                <form action="<?= url('danios/' . $d['id'] . '/fotos/' . $f['id'] . '/delete') ?>" method="post" class="danio-foto-delete"
                      onsubmit="return confirm('¿Eliminar esta fotografía?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">×</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-muted mb-0">No hay fotografías registradas para este daño.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($seguimiento)): ?>
<div class="card mb-2">
    <div class="card-header"><h3>Seguimiento</h3></div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Cambio</th><th>Comentario</th></tr></thead>
            <tbody>
                <?php foreach ($seguimiento as $s): ?>
                <tr>
                    <td><?= format_datetime($s['created_at']) ?></td>
                    <td><?= e($s['usuario'] ?? '—') ?></td>
                    <td><?= e(str_replace('_', ' ', $s['estado_anterior'])) ?> → <?= e(str_replace('_', ' ', $s['estado_nuevo'])) ?></td>
                    <td><?= e($s['comentario'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (can('danios.update') && ($d['estado'] ?? '') !== 'reparado' && ($d['estado'] ?? '') !== 'cerrado_sin_accion'): ?>
<div class="card">
    <div class="card-header"><h3>Actualizar estado</h3></div>
    <div class="card-body">
        <form action="<?= url('danios/' . $d['id'] . '/estado') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="estado">Nuevo estado</label>
                    <select id="estado" name="estado" class="form-select" required>
                        <?php foreach ($estados as $est): ?>
                        <option value="<?= e($est) ?>" <?= ($d['estado'] ?? '') === $est ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $est))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:2">
                    <label class="form-label" for="comentario">Comentario</label>
                    <input type="text" id="comentario" name="comentario" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar</button>
        </form>
    </div>
</div>
<?php endif; ?>
