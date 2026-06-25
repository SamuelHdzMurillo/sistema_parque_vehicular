<?php
$pageTitle = 'Herramientas — ' . ($vehiculo['numero_economico'] ?? '');
$v = $vehiculo ?? [];
$herramientas = $herramientas ?? [];
$estadosHerr = $estados ?? \App\Services\HerramientaService::ESTADOS;
$catalogCodes = herramienta_catalogo_codigos();
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb">
            <li><a href="<?= url('vehiculos') ?>">Vehículos</a></li>
            <li><a href="<?= url('vehiculos/' . ($v['id'] ?? '')) ?>"><?= e($v['numero_economico'] ?? '') ?></a></li>
            <li>/ Herramientas</li>
        </ul>
        <h1 class="page-title">Inventario de herramientas</h1>
        <p class="page-subtitle">Vehículo <?= e($v['numero_economico'] ?? '') ?> — Placas <?= e($v['placas'] ?? '') ?></p>
    </div>
</div>

<div class="card">
    <?php if (can('herramientas.update')): ?>
    <form action="<?= url('herramientas/vehiculo/' . $v['id']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Herramienta / Equipo</th><th>Estado</th><th>Vencimiento</th><th>Observaciones</th></tr></thead>
                <tbody>
                    <?php foreach ($herramientas as $h): ?>
                    <?php
                    $tipo = (string) ($h['tipo'] ?? '');
                    $registrada = (bool) ($h['registrada'] ?? true);
                    $esCatalogo = in_array($tipo, $catalogCodes, true);
                    ?>
                    <tr<?= !$registrada ? ' class="text-muted"' : '' ?>>
                        <td>
                            <strong><?= e(herramienta_nombre($tipo)) ?></strong>
                            <?php if (!$registrada): ?>
                            <span class="badge badge-secondary" style="margin-left:.35rem;font-weight:normal">Sin registrar</span>
                            <?php elseif (!$esCatalogo): ?>
                            <span class="badge badge-info" style="margin-left:.35rem;font-weight:normal">Adicional</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <select name="herramientas[<?= e($tipo) ?>]" class="form-select">
                                <?php foreach ($estadosHerr as $val => $label): ?>
                                <option value="<?= e($val) ?>" <?= ($h['estado'] ?? 'presente') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><?= $registrada ? format_date($h['fecha_vencimiento'] ?? null) : '—' ?></td>
                        <td><?= $registrada ? e($h['observaciones'] ?? '—') : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex gap-1 flex-wrap">
            <button type="submit" class="btn btn-primary">Guardar inventario</button>
            <a href="<?= url('vehiculos/' . $v['id']) ?>" class="btn btn-secondary">Volver al expediente</a>
        </div>
    </form>

    <div class="card-body border-top">
        <h3 style="margin:0 0 .75rem;font-size:1rem">Agregar otra herramienta</h3>
        <p class="card-header-hint mb-2">Si el equipo no aparece en la lista, regístrelo aquí. Quedará disponible para comisiones e inspecciones.</p>
        <form action="<?= url('herramientas/vehiculo/' . $v['id']) ?>" method="post" class="form-row" style="align-items:flex-end;gap:.75rem">
            <?= csrf_field() ?>
            <div class="form-group" style="flex:2;min-width:200px;margin-bottom:0">
                <label class="form-label" for="nueva_herramienta_nombre">Nombre</label>
                <input type="text" id="nueva_herramienta_nombre" name="nueva_herramienta_nombre" class="form-control" maxlength="40" placeholder="Ej. Cables pasa corriente, Chaleco reflectante…" required>
            </div>
            <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0">
                <label class="form-label" for="nueva_herramienta_estado">Estado</label>
                <select id="nueva_herramienta_estado" name="nueva_herramienta_estado" class="form-select">
                    <?php foreach ($estadosHerr as $val => $label): ?>
                    <option value="<?= e($val) ?>" <?= $val === 'presente' ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0">
                <button type="submit" class="btn btn-accent">Agregar</button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Herramienta</th><th>Estado</th><th>Vencimiento</th><th>Observaciones</th></tr></thead>
            <tbody>
                <?php if (empty($herramientas)): ?>
                <tr><td colspan="4" class="text-center text-muted">Sin herramientas registradas</td></tr>
                <?php else: foreach ($herramientas as $h): ?>
                <tr>
                    <td><?= e(herramienta_nombre((string) ($h['tipo'] ?? ''))) ?></td>
                    <td><span class="badge <?= ($h['estado'] ?? '') === 'presente' ? 'badge-success' : 'badge-warning' ?>"><?= e($estadosHerr[$h['estado']] ?? $h['estado']) ?></span></td>
                    <td><?= format_date($h['fecha_vencimiento'] ?? null) ?></td>
                    <td><?= e($h['observaciones'] ?? '—') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
