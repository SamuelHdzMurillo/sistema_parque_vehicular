<?php
$pageTitle = 'Comisión ' . ($comision['folio'] ?? '');
$c = $comision ?? [];
$estados = ['borrador' => 'Borrador', 'en_curso' => 'En curso', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('comisiones') ?>">Comisiones</a></li><li>/ <?= e($c['folio']) ?></li></ul>
        <h1 class="page-title">Comisión <?= e($c['folio']) ?></h1>
        <p class="page-subtitle">
            <span class="badge badge-secondary"><?= e($estados[$c['estado']] ?? $c['estado']) ?></span>
            — Vehículo <?= e($c['numero_economico'] ?? '—') ?>
        </p>
    </div>
    <div class="page-actions">
        <?php if (can('comisiones.update') && in_array($c['estado'], ['borrador', 'en_curso'], true)): ?>
        <a href="<?= url('comisiones/' . $c['id'] . '/edit') ?>" class="btn btn-secondary">Editar</a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <div class="meta-grid">
            <div class="meta-item"><label>Fecha</label><span><?= format_date($c['fecha']) ?></span></div>
            <div class="meta-item"><label>Hora salida</label><span><?= e(substr($c['hora_salida'] ?? '', 0, 5)) ?></span></div>
            <div class="meta-item"><label>Hora regreso</label><span><?= $c['hora_regreso'] ? e(substr($c['hora_regreso'], 0, 5)) : '—' ?></span></div>
            <div class="meta-item"><label>Conductor</label><span><?= e($c['conductor_nombre']) ?></span></div>
            <div class="meta-item"><label>Área</label><span><?= e($c['area_nombre'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Responsable</label><span><?= e($c['responsable_nombre'] ?? '—') ?></span></div>
            <div class="meta-item"><label>Km salida</label><span><?= number_format((int) $c['km_salida']) ?></span></div>
            <div class="meta-item"><label>Km regreso</label><span><?= $c['km_regreso'] !== null ? number_format((int) $c['km_regreso']) : '—' ?></span></div>
            <div class="meta-item"><label>Km recorridos</label><span><?= $c['km_recorridos'] !== null ? number_format((int) $c['km_recorridos']) : '—' ?></span></div>
            <div class="meta-item"><label>Comb. salida</label><span><?= e((string) $c['combustible_salida']) ?>%</span></div>
            <div class="meta-item"><label>Comb. regreso</label><span><?= $c['combustible_regreso'] !== null ? e((string) $c['combustible_regreso']) . '%' : '—' ?></span></div>
            <div class="meta-item"><label>Rendimiento</label><span><?= $c['rendimiento'] !== null ? number_format((float) $c['rendimiento'], 2) . ' km/L' : '—' ?></span></div>
        </div>
        <p class="mt-2"><strong>Destino:</strong> <?= e($c['destino']) ?></p>
        <p><strong>Motivo:</strong> <?= e($c['motivo']) ?></p>
        <?php if (!empty($c['observaciones'])): ?>
        <p><strong>Observaciones:</strong> <?= e($c['observaciones']) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($c['estado'] === 'borrador' && can('comisiones.update')): ?>
<div class="card mb-2">
    <div class="card-header"><h3>Iniciar comisión</h3></div>
    <div class="card-body">
        <p class="text-muted">Al iniciar, el vehículo pasará a estado «En comisión» y no podrá asignarse a otra salida.</p>
        <form action="<?= url('comisiones/' . $c['id'] . '/iniciar') ?>" method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-accent" data-confirm="¿Confirma iniciar esta comisión?">Iniciar comisión</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($c['estado'] === 'en_curso' && can('comisiones.update')): ?>
<div class="card mb-2">
    <div class="card-header"><h3>Finalizar comisión</h3></div>
    <div class="card-body">
        <form action="<?= url('comisiones/' . $c['id'] . '/finalizar') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="hora_regreso">Hora regreso <span class="required">*</span></label>
                    <input type="time" id="hora_regreso" name="hora_regreso" class="form-control" required value="<?= e(date('H:i')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="km_regreso">Km regreso <span class="required">*</span></label>
                    <input type="number" id="km_regreso" name="km_regreso" class="form-control" required min="<?= (int) $c['km_salida'] ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="combustible_regreso">Combustible regreso (%) <span class="required">*</span></label>
                    <input type="number" id="combustible_regreso" name="combustible_regreso" class="form-control" required min="0" max="100" step="0.01">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="observaciones_fin">Observaciones de regreso</label>
                <textarea id="observaciones_fin" name="observaciones" class="form-textarea"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Finalizar comisión</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (in_array($c['estado'], ['borrador', 'en_curso'], true) && can('comisiones.delete')): ?>
<div class="card">
    <div class="card-header"><h3>Cancelar comisión</h3></div>
    <div class="card-body">
        <form action="<?= url('comisiones/' . $c['id'] . '/cancelar') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="motivo_cancel">Motivo de cancelación</label>
                <textarea id="motivo_cancel" name="motivo" class="form-textarea" required></textarea>
            </div>
            <button type="submit" class="btn btn-danger" data-confirm="¿Confirma cancelar esta comisión?">Cancelar comisión</button>
        </form>
    </div>
</div>
<?php endif; ?>
