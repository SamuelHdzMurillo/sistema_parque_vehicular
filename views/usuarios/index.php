<?php
$pageTitle = 'Usuarios';
$data = $data ?? [];
$roles = $roles ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Usuarios del sistema</h1>
        <p class="page-subtitle">Cuentas de acceso, roles y qué puede hacer cada persona</p>
    </div>
    <?php if (can('usuarios.create')): ?>
    <div class="page-actions"><a href="<?= url('usuarios/create') ?>" class="btn btn-primary">+ Nuevo usuario</a></div>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <?php App\Core\View::component('roles-guia', ['roles' => $roles]); ?>
</div>

<div class="card">
    <form class="filters-bar card-body pb-0" method="get">
        <div class="form-group">
            <label class="form-label" for="q">Buscar usuario</label>
            <input type="search" id="q" name="q" class="form-control" placeholder="Nombre o correo…" value="<?= e($_GET['q'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-info">Buscar</button>
    </form>

    <?php if (empty($data)): ?>
    <div class="empty-state py-5 text-center text-muted">No hay usuarios que coincidan con la búsqueda.</div>
    <?php else: ?>
    <div class="usuarios-list">
        <?php foreach ($data as $u): ?>
        <?php
        $roleSlug = (string) ($u['role_slug'] ?? '');
        $nombre = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido_paterno'] ?? ''));
        $permisosGrupos = $u['permisos_grupos'] ?? [];
        $totalPermisos = count($u['permisos'] ?? []);
        ?>
        <article class="card usuario-card usuario-card--<?= e($roleSlug) ?>">
            <div class="usuario-card-header">
                <div class="usuario-card-info">
                    <h2 class="usuario-card-nombre"><?= e($nombre) ?></h2>
                    <p class="usuario-card-meta">
                        <?= e($u['email']) ?>
                        <?php if (!empty($u['area'])): ?>
                        · <?= e($u['area']) ?>
                        <?php endif; ?>
                    </p>
                    <p class="usuario-card-resumen"><?= e($u['permisos_resumen'] ?? '') ?></p>
                </div>
                <div class="usuario-card-badges">
                    <span class="badge <?= rol_badge_class($roleSlug) ?>" title="<?= e((string) ($u['rol_descripcion'] ?? '')) ?>">
                        <?= e($u['rol'] ?? '—') ?>
                    </span>
                    <?php if (!empty($u['activo'])): ?>
                    <span class="badge badge-success">Activo</span>
                    <?php else: ?>
                    <span class="badge badge-secondary">Inactivo</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($u['rol_descripcion'])): ?>
            <p class="usuario-card-rol-desc"><?= e((string) $u['rol_descripcion']) ?></p>
            <?php endif; ?>

            <details class="usuario-permisos-details">
                <summary class="usuario-permisos-summary">
                    <span>Ver todo lo que puede hacer</span>
                    <span class="usuario-permisos-count"><?= $totalPermisos ?> permiso<?= $totalPermisos === 1 ? '' : 's' ?></span>
                </summary>
                <div class="usuario-permisos-details-body">
                    <?php App\Core\View::component('usuario-permisos', ['permisos_grupos' => $permisosGrupos]); ?>
                </div>
            </details>

            <?php if (can('usuarios.update')): ?>
            <div class="usuario-card-actions">
                <a href="<?= url('usuarios/' . $u['id'] . '/edit') ?>" class="btn btn-sm btn-accent">Editar usuario</a>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="card-body pt-0">
        <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 15]); ?>
    </div>
</div>
