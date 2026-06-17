<?php
$pageTitle = 'Áreas';
$data = $data ?? [];
$planteles = $planteles ?? [];
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li>/ Áreas</li></ul>
        <h1 class="page-title">Áreas</h1>
        <p class="page-subtitle">Departamentos vinculados a un plantel — formato en comisiones: <em>Área - Plantel</em></p>
    </div>
    <?php if (can('catalogos.create')): ?>
    <div class="page-actions"><a href="<?= url('catalogos/areas/create') ?>" class="btn btn-primary">+ Nueva área</a></div>
    <?php endif; ?>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'areas']); ?>

<div class="card">
    <form class="filters-bar" method="get">
        <div class="form-group">
            <label class="form-label" for="q">Buscar</label>
            <input type="search" id="q" name="q" class="form-control" placeholder="Clave, nombre o plantel…" value="<?= e((string) ($_GET['q'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="plantel_id">Plantel</label>
            <select id="plantel_id" name="plantel_id" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($planteles as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= (string) ($_GET['plantel_id'] ?? '') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['clave'] . ' — ' . $p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="activo">Estado</label>
            <select id="activo" name="activo" class="form-select">
                <option value="">Todos</option>
                <option value="1" <?= ($_GET['activo'] ?? '') === '1' ? 'selected' : '' ?>>Activas</option>
                <option value="0" <?= ($_GET['activo'] ?? '') === '0' ? 'selected' : '' ?>>Inactivas</option>
            </select>
        </div>
        <button type="submit" class="btn btn-info">Filtrar</button>
    </form>
    <div class="card-header">
        <h3>Listado de áreas</h3>
        <span class="text-muted"><?= (int) ($total ?? 0) ?> registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table dash-table">
            <thead><tr><th>Clave</th><th>Nombre</th><th>Plantel</th><th>Etiqueta en comisiones</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="6" class="text-center text-muted">Sin áreas registradas</td></tr>
                <?php else: foreach ($data as $a): ?>
                <tr>
                    <td><code><?= e($a['clave']) ?></code></td>
                    <td><strong><?= e($a['nombre']) ?></strong></td>
                    <td><?= e(trim(($a['plantel_clave'] ?? '') . ' — ' . ($a['plantel_nombre'] ?? ''), ' —')) ?: '—' ?></td>
                    <td><span class="badge badge-secondary"><?= e($a['label'] ?? catalogo_area_label($a)) ?></span></td>
                    <td>
                        <?php if ((int) $a['activo'] === 1): ?>
                        <span class="badge badge-success">Activa</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-flex gap-1">
                        <?php if (can('catalogos.update')): ?>
                        <a href="<?= url('catalogos/areas/' . $a['id'] . '/edit') ?>" class="btn btn-sm btn-accent">Editar</a>
                        <form action="<?= url('catalogos/areas/' . $a['id'] . '/toggle') ?>" method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="activo" value="<?= (int) $a['activo'] === 1 ? '0' : '1' ?>">
                            <button type="submit" class="btn btn-sm btn-secondary"><?= (int) $a['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button>
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
