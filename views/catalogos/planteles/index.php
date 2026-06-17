<?php
$pageTitle = 'Planteles';
$data = $data ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li>/ Planteles</li></ul>
        <h1 class="page-title">Planteles</h1>
        <p class="page-subtitle">Sedes y campus donde se ubican las áreas</p>
    </div>
    <?php if (can('catalogos.create')): ?>
    <div class="page-actions"><a href="<?= url('catalogos/planteles/create') ?>" class="btn btn-primary">+ Nuevo plantel</a></div>
    <?php endif; ?>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'planteles']); ?>

<div class="card">
    <form class="filters-bar" method="get">
        <div class="form-group">
            <label class="form-label" for="q">Buscar</label>
            <input type="search" id="q" name="q" class="form-control" placeholder="Clave o nombre…" value="<?= e((string) ($_GET['q'] ?? '')) ?>">
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
        <h3>Listado de planteles</h3>
        <span class="text-muted"><?= (int) ($total ?? 0) ?> registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table dash-table">
            <thead><tr><th>Clave</th><th>Nombre</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="4" class="text-center text-muted">Sin planteles registrados</td></tr>
                <?php else: foreach ($data as $p): ?>
                <tr>
                    <td><code><?= e($p['clave']) ?></code></td>
                    <td><strong><?= e($p['nombre']) ?></strong></td>
                    <td>
                        <?php if ((int) $p['activo'] === 1): ?>
                        <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-flex gap-1">
                        <?php if (can('catalogos.update')): ?>
                        <a href="<?= url('catalogos/planteles/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-accent">Editar</a>
                        <form action="<?= url('catalogos/planteles/' . $p['id'] . '/toggle') ?>" method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="activo" value="<?= (int) $p['activo'] === 1 ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-sm btn-secondary"><?= (int) $p['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button>
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
