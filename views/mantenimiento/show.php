<?php
$pageTitle = 'Mantenimiento ' . ($mantenimiento['folio'] ?? '');
$m = $mantenimiento ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('mantenimiento') ?>">Mantenimiento</a></li><li>/ <?= e($m['folio'] ?? '') ?></li></ul>
        <h1 class="page-title"><?= e($m['folio'] ?? '') ?></h1>
        <p class="page-subtitle"><span class="badge badge-secondary"><?= e(str_replace('_', ' ', $m['estado'] ?? '')) ?></span></p>
    </div>
    <div class="page-actions">
        <a href="<?= url('formatos/mantenimiento/' . $m['id']) ?>" class="btn btn-secondary" target="_blank">Descargar PDF / Imprimir</a>
        <?php if (can('mantenimiento.update')): ?>
        <a href="<?= url('mantenimiento/' . $m['id'] . '/edit') ?>" class="btn btn-secondary">Editar</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <div class="meta-grid">
            <div class="meta-item"><label>Vehículo</label><span><?= e($m['numero_economico'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Tipo</label><span><?= e(ucfirst($m['tipo'] ?? '')) ?></span></div>
            <div class="meta-item"><label>Fecha</label><span><?= format_date($m['fecha'] ?? null) ?></span></div>
            <div class="meta-item"><label>Kilometraje</label><span><?= number_format((int) ($m['kilometraje'] ?? 0)) ?></span></div>
            <div class="meta-item"><label>Proveedor</label><span><?= e($m['proveedor_nombre'] ?? $m['razon_social'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Costo</label><span><?= format_money($m['costo'] ?? 0) ?></span></div>
            <div class="meta-item"><label>Responsable</label><span><?= e($m['responsable_nombre'] ?? '—') ?></span></div>
        </div>
        <p class="mt-2"><strong>Descripción:</strong> <?= e($m['descripcion'] ?? '') ?></p>
        <?php if (!empty($m['observaciones'])): ?>
        <p><strong>Observaciones:</strong> <?= e($m['observaciones']) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (($m['estado'] ?? '') === 'pendiente' && can('mantenimiento.authorize')): ?>
<div class="card mb-2">
    <div class="card-header"><h3>Autorizar servicio</h3></div>
    <div class="card-body">
        <form action="<?= url('mantenimiento/' . $m['id'] . '/autorizar') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-accent">Autorizar mantenimiento</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (in_array($m['estado'] ?? '', ['autorizado', 'en_proceso'], true) && can('mantenimiento.update')): ?>
<div class="card">
    <div class="card-header"><h3>Finalizar servicio</h3></div>
    <div class="card-body">
        <form action="<?= url('mantenimiento/' . $m['id'] . '/finalizar') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="costo_final">Costo final</label>
                    <input type="number" id="costo_final" name="costo" class="form-control" step="0.01" value="<?= e((string) ($m['costo'] ?? '0')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="observaciones_fin">Observaciones</label>
                    <input type="text" id="observaciones_fin" name="observaciones" class="form-control">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Marcar como finalizado</button>
        </form>
    </div>
</div>
<?php endif; ?>
