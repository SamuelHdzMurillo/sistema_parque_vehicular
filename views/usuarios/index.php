<?php $pageTitle = 'Usuarios'; $data = $data ?? []; ?>
<div class="page-header">
    <div>
        <h1 class="page-title">Usuarios del sistema</h1>
        <p class="page-subtitle">Gestión de cuentas, roles y permisos</p>
    </div>
    <?php if (can('usuarios.create')): ?>
    <div class="page-actions"><a href="<?= url('usuarios/create') ?>" class="btn btn-primary">+ Nuevo usuario</a></div>
    <?php endif; ?>
</div>
<div class="card">
    <form class="filters-bar" method="get">
        <div class="form-group">
            <label class="form-label" for="q">Buscar</label>
            <input type="search" id="q" name="q" class="form-control" placeholder="Nombre o correo…" value="<?= e($_GET['q'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-secondary">Buscar</button>
    </form>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Área</th><th>Estado</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="6" class="text-center text-muted">Sin usuarios</td></tr>
                <?php else: foreach ($data as $u): ?>
                <tr>
                    <td><strong><?= e(trim(($u['nombre'] ?? '') . ' ' . ($u['apellido_paterno'] ?? ''))) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e($u['rol'] ?? '—') ?></td>
                    <td><?= e($u['area'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($u['activo'])): ?>
                        <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (can('usuarios.update')): ?>
                        <a href="<?= url('usuarios/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-secondary">Editar</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
</div>
