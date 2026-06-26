<?php
$pageTitle = 'Documentos';
$grupos = $grupos ?? [];
$vehiculos = $vehiculos ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Documentación vehicular</h1>
        <p class="page-subtitle">Pólizas, verificaciones, tarjetas de circulación y más</p>
    </div>
    <?php if (can('documentos.create')): ?>
    <div class="page-actions"><a href="<?= url('documentos/create') ?>" class="btn btn-primary">+ Subir documento</a></div>
    <?php endif; ?>
</div>
<div class="card">
    <form class="filters-bar" method="get" action="<?= url('documentos') ?>">
        <div class="form-group">
            <label class="form-label" for="vehiculo_id">Vehículo</label>
            <select id="vehiculo_id" name="vehiculo_id" class="form-select">
                <option value="">Todos los vehículos</option>
                <?php foreach ($vehiculos as $v): ?>
                <option value="<?= (int) $v['id'] ?>" <?= ($_GET['vehiculo_id'] ?? '') == $v['id'] ? 'selected' : '' ?>>
                    <?= e(catalogo_vehiculo_label($v)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-info">Filtrar</button>
        <?php if (!empty($_GET['vehiculo_id'])): ?>
        <a href="<?= url('documentos') ?>" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($grupos)): ?>
    <div class="empty-state p-3">
        <p class="text-muted text-center">Sin documentos registrados</p>
    </div>
    <?php else: ?>
    <?php foreach ($grupos as $grupo): ?>
    <section class="doc-vehiculo-group">
        <header class="doc-vehiculo-header">
            <div>
                <?php if (can('expediente.read')): ?>
                <h2 class="doc-vehiculo-title">
                    <a href="<?= url('vehiculos/' . (int) $grupo['vehiculo_id']) ?>"><?= e($grupo['numero_economico']) ?></a>
                </h2>
                <?php else: ?>
                <h2 class="doc-vehiculo-title"><?= e($grupo['numero_economico']) ?></h2>
                <?php endif; ?>
                <p class="doc-vehiculo-meta">
                    <?= e(trim(($grupo['marca'] ?? '') . ' ' . ($grupo['modelo'] ?? ''))) ?>
                    <?php if (!empty($grupo['placas'])): ?>
                    · Placas <?= e($grupo['placas']) ?>
                    <?php endif; ?>
                    · <?= count($grupo['documentos']) ?> documento<?= count($grupo['documentos']) !== 1 ? 's' : '' ?>
                </p>
            </div>
            <?php if (can('documentos.create')): ?>
            <a href="<?= url('documentos/create') ?>?vehiculo_id=<?= (int) $grupo['vehiculo_id'] ?>" class="btn btn-sm btn-secondary">+ Subir</a>
            <?php endif; ?>
        </header>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>No. documento</th>
                        <th>Vencimiento</th>
                        <th>Tiempo restante</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupo['documentos'] as $d): ?>
                    <?php
                    $dias = isset($d['dias_restantes']) ? (int) $d['dias_restantes'] : null;
                    $archivoUrl = !empty($d['archivo_ruta'])
                        ? url('storage/uploads/' . ltrim((string) $d['archivo_ruta'], '/'))
                        : '';
                    ?>
                    <tr>
                        <td><?= e(documento_tipo_label((string) $d['tipo'])) ?></td>
                        <td><?= e($d['titulo']) ?></td>
                        <td><?= e($d['numero_documento'] ?? '—') ?></td>
                        <td><?= format_date($d['fecha_vencimiento']) ?></td>
                        <td>
                            <span class="badge <?= vencimiento_badge_class($d['fecha_vencimiento'], $dias) ?>">
                                <?= e(format_tiempo_restante($d['fecha_vencimiento'], $dias)) ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <?php if ($archivoUrl !== ''): ?>
                            <a href="<?= e($archivoUrl) ?>" class="btn btn-sm btn-info" target="_blank" rel="noopener">Ver</a>
                            <a href="<?= url('documentos/' . $d['id'] . '/download') ?>" class="btn btn-sm btn-secondary">Descargar</a>
                            <?php else: ?>
                            <span class="text-muted">Sin archivo</span>
                            <?php endif; ?>
                            <?php if (can('documentos.update')): ?>
                            <a href="<?= url('documentos/' . $d['id'] . '/edit') ?>" class="btn btn-sm btn-secondary">Actualizar</a>
                            <form action="<?= url('documentos/' . $d['id'] . '/eliminar') ?>" method="post" class="inline-form">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger" data-confirm="¿Confirma eliminar el documento «<?= e($d['titulo']) ?>»?">Eliminar</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
