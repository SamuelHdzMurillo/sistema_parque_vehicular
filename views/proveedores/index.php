<?php
$pageTitle = 'Proveedores';
$data = $data ?? [];
$tipos = $tipos ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Proveedores</h1>
        <p class="page-subtitle">Talleres, gasolineras y proveedores de servicios</p>
    </div>
    <?php if (can('proveedores.create')): ?>
    <div class="page-actions"><a href="<?= url('proveedores/create') ?>" class="btn btn-primary">+ Nuevo proveedor</a></div>
    <?php endif; ?>
</div>
<div class="card">
    <form class="filters-bar" method="get">
        <div class="form-group">
            <label class="form-label" for="q">Buscar</label>
            <input type="text" id="q" name="q" class="form-control" placeholder="Razón social o RFC" value="<?= e((string) ($_GET['q'] ?? '')) ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="tipo">Tipo</label>
            <select id="tipo" name="tipo" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($tipos as $t): ?>
                <option value="<?= e($t) ?>" <?= ($_GET['tipo'] ?? '') === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
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
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Razón social</th><th>RFC</th><th>Teléfono</th><th>Email</th><th>Tipo</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="7" class="text-center text-muted">Sin registros</td></tr>
                <?php else: foreach ($data as $p): ?>
                <tr>
                    <td><strong><?= e($p['razon_social']) ?></strong></td>
                    <td><?= e($p['rfc'] ?? '—') ?></td>
                    <td><?= e($p['telefono'] ?? '—') ?></td>
                    <td><?= e($p['email'] ?? '—') ?></td>
                    <td><?= e(ucfirst($p['tipo'])) ?></td>
                    <td>
                        <?php if ((int) $p['activo'] === 1): ?>
                        <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-flex gap-1">
                        <?php if (can('proveedores.update')): ?>
                        <a href="<?= url('proveedores/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-info">Editar</a>
                        <form action="<?= url('proveedores/' . $p['id'] . '/toggle') ?>" method="post" class="d-inline">
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
