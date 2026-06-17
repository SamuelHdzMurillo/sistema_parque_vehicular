<?php
$pageTitle = 'Conductores';
$data = $data ?? [];
$areas = $areas ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li>/ Conductores</li></ul>
        <h1 class="page-title">Conductores</h1>
        <p class="page-subtitle">Personas autorizadas para conducir en comisiones</p>
    </div>
    <?php if (can('catalogos.create')): ?>
    <div class="page-actions"><a href="<?= url('catalogos/conductores/create') ?>" class="btn btn-primary">+ Nuevo conductor</a></div>
    <?php endif; ?>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'conductores']); ?>

<div class="card">
    <form class="filters-bar" method="get">
        <div class="form-group">
            <label class="form-label" for="q">Buscar</label>
            <input type="search" id="q" name="q" class="form-control" placeholder="Nombre, teléfono o área…" value="<?= e((string) ($_GET['q'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="area_id">Área</label>
            <select id="area_id" name="area_id" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($areas as $a): ?>
                <option value="<?= (int) $a['id'] ?>" <?= (string) ($_GET['area_id'] ?? '') === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['label'] ?? catalogo_area_label($a)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="activo">Estado</label>
            <select id="activo" name="activo" class="form-select">
                <option value="">Todos</option>
                <option value="1" <?= ($_GET['activo'] ?? '') === '1' ? 'selected' : '' ?>>Activos</option>
                <option value="0" <?= ($_GET['activo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivos</option>
            </select>
        </div>
        <button type="submit" class="btn btn-info">Filtrar</button>
    </form>
    <div class="card-header">
        <h3>Listado de conductores</h3>
        <span class="text-muted"><?= (int) ($total ?? 0) ?> registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table dash-table">
            <thead><tr><th>Nombre</th><th>Área</th><th>Teléfono</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="5" class="text-center text-muted">Sin conductores registrados</td></tr>
                <?php else: foreach ($data as $c): ?>
                <tr>
                    <td><strong><?= e($c['nombre']) ?></strong></td>
                    <td><span class="badge badge-secondary"><?= e($c['area_label'] ?? catalogo_area_label($c)) ?></span></td>
                    <td><?= e($c['telefono']) ?></td>
                    <td>
                        <?php if ((int) $c['activo'] === 1): ?>
                        <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-flex gap-1">
                        <?php if (can('catalogos.update')): ?>
                        <a href="<?= url('catalogos/conductores/' . $c['id'] . '/edit') ?>" class="btn btn-sm btn-accent">Editar</a>
                        <form action="<?= url('catalogos/conductores/' . $c['id'] . '/toggle') ?>" method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="activo" value="<?= (int) $c['activo'] === 1 ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-sm btn-secondary"><?= (int) $c['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
