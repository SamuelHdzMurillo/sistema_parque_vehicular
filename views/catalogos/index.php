<?php
$pageTitle = 'Catálogos';
$stats = $stats ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Catálogos del sistema</h1>
        <p class="page-subtitle">Planteles, áreas solicitantes y conductores para comisiones</p>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'inicio']); ?>

<div class="dash-kpi-strip catalogos-kpi-strip">
    <div class="dash-kpi">
        <span class="dash-kpi-label">Planteles</span>
        <span class="dash-kpi-value text-info"><?= (int) ($stats['planteles'] ?? 0) ?></span>
        <span class="dash-kpi-note">sedes registradas</span>
    </div>
    <div class="dash-kpi">
        <span class="dash-kpi-label">Áreas</span>
        <span class="dash-kpi-value"><?= (int) ($stats['areas'] ?? 0) ?></span>
        <span class="dash-kpi-note">departamentos por plantel</span>
    </div>
    <div class="dash-kpi">
        <span class="dash-kpi-label">Conductores</span>
        <span class="dash-kpi-value text-success"><?= (int) ($stats['conductores'] ?? 0) ?></span>
        <span class="dash-kpi-note">en catálogo activo</span>
    </div>
</div>

<section class="dash-section">
    <div class="dash-section-head">
        <div>
            <h2 class="dash-section-title">Administración de catálogos</h2>
            <p class="dash-section-desc">Seleccione el catálogo que desea consultar o dar de alta</p>
        </div>
    </div>

    <div class="dash-activity-grid catalogos-hub-grid">
        <div class="card">
            <div class="card-header">
                <div>
                    <h3>Planteles</h3>
                    <p class="card-header-hint">Dirección General, planteles y sedes</p>
                </div>
                <span class="badge badge-info"><?= (int) ($stats['planteles'] ?? 0) ?></span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">Defina las sedes donde se ubican las áreas operativas (ej. DG, LP, CAB).</p>
                <div class="d-flex gap-1 flex-wrap">
                    <a href="<?= url('catalogos/planteles') ?>" class="btn btn-primary btn-sm">Ver planteles</a>
                    <?php if (can('catalogos.create')): ?>
                    <a href="<?= url('catalogos/planteles/create') ?>" class="btn btn-accent btn-sm">+ Nuevo</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3>Áreas</h3>
                    <p class="card-header-hint">Área solicitante en comisiones</p>
                </div>
                <span class="badge badge-primary"><?= (int) ($stats['areas'] ?? 0) ?></span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">Departamentos vinculados a un plantel. Se muestran como <em>Jurídico - DG</em>.</p>
                <div class="d-flex gap-1 flex-wrap">
                    <a href="<?= url('catalogos/areas') ?>" class="btn btn-primary btn-sm">Ver áreas</a>
                    <?php if (can('catalogos.create')): ?>
                    <a href="<?= url('catalogos/areas/create') ?>" class="btn btn-accent btn-sm">+ Nueva</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card catalogos-hub-card-full">
            <div class="card-header">
                <div>
                    <h3>Conductores</h3>
                    <p class="card-header-hint">Personas autorizadas a conducir</p>
                </div>
                <span class="badge badge-success"><?= (int) ($stats['conductores'] ?? 0) ?></span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">Nombre, área de adscripción y teléfono. Aparecen al registrar comisiones.</p>
                <div class="d-flex gap-1 flex-wrap">
                    <a href="<?= url('catalogos/conductores') ?>" class="btn btn-primary btn-sm">Ver conductores</a>
                    <?php if (can('catalogos.create')): ?>
                    <a href="<?= url('catalogos/conductores/create') ?>" class="btn btn-accent btn-sm">+ Nuevo</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="card mt-2">
    <?php App\Core\View::component('catalogo-guia', ['variant' => 'general']); ?>
</div>
