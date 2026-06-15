<?php
$pageTitle = 'Configuración de alertas';
$config = $config ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('alertas') ?>">Alertas</a></li><li>/ Configuración</li></ul>
        <h1 class="page-title">Configuración de alertas</h1>
        <p class="page-subtitle">Umbrales de kilometraje y días para generación automática</p>
    </div>
</div>

<div class="card">
    <form action="<?= url('alertas/config') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Unidad</th>
                        <th>Umbral verde</th>
                        <th>Umbral amarillo</th>
                        <th>Umbral rojo</th>
                        <th>Activo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($config)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No hay configuración cargada</td></tr>
                    <?php else: foreach ($config as $idx => $row): ?>
                    <tr>
                        <td>
                            <?= e($row['tipo'] ?? '') ?>
                            <input type="hidden" name="config[<?= $idx ?>][tipo]" value="<?= e($row['tipo'] ?? '') ?>">
                        </td>
                        <td>
                            <input type="text" name="config[<?= $idx ?>][nombre]" class="form-control" value="<?= e($row['nombre'] ?? '') ?>">
                        </td>
                        <td><?= e($row['unidad'] ?? 'km') ?></td>
                        <td>
                            <input type="number" name="config[<?= $idx ?>][umbral_verde]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_verde'] ?? '')) ?>">
                        </td>
                        <td>
                            <input type="number" name="config[<?= $idx ?>][umbral_amarillo]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_amarillo'] ?? '')) ?>">
                        </td>
                        <td>
                            <input type="number" name="config[<?= $idx ?>][umbral_rojo]" class="form-control" min="0"
                                   value="<?= e((string) ($row['umbral_rojo'] ?? '')) ?>">
                        </td>
                        <td>
                            <label class="form-check">
                                <input type="checkbox" name="config[<?= $idx ?>][activo]" value="1" <?= !empty($row['activo']) ? 'checked' : '' ?>>
                                Activo
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <p class="form-hint mt-2">Los umbrales en kilómetros indican cuántos km desde el último servicio generan alerta verde, amarilla o roja.</p>
        <div class="mt-2">
            <button type="submit" class="btn btn-primary">Guardar configuración</button>
            <a href="<?= url('alertas') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
