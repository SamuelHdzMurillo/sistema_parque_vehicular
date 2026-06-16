<?php
$currentPath = $currentPath ?? '';
$isActive = static function (string $segment) use ($currentPath): bool {
    if ($segment === '' || $segment === 'dashboard') {
        return $currentPath === '' || $currentPath === 'dashboard';
    }
    return $currentPath === $segment || str_starts_with($currentPath, $segment . '/');
};
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <?= brand_logo_img('sidebar-brand-logo') ?>
        <div class="sidebar-brand-text">
            <strong>Control Vehicular</strong>
            <small><?= e((string) config('app', 'institution')) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if (can('dashboard.read')): ?>
        <div class="nav-section">Principal</div>
        <a href="<?= url('dashboard') ?>" class="nav-link <?= $isActive('dashboard') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>
        <?php endif; ?>

        <?php if (can('vehiculos.read') || can('expediente.read')): ?>
        <div class="nav-section">Parque vehicular</div>
        <?php if (can('vehiculos.read')): ?>
        <a href="<?= url('vehiculos') ?>" class="nav-link <?= $isActive('vehiculos') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17h-.5A2.5 2.5 0 0 1 4 14.5v-3A2.5 2.5 0 0 1 6.5 9H7"/><path d="M17 17h.5A2.5 2.5 0 0 0 20 14.5v-3A2.5 2.5 0 0 0 17.5 9H17"/><path d="M5 9l2-4h10l2 4"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
            Vehículos
        </a>
        <?php endif; ?>
        <?php if (can('comisiones.read')): ?>
        <a href="<?= url('comisiones') ?>" class="nav-link <?= $isActive('comisiones') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
            Comisiones
        </a>
        <?php endif; ?>
        <?php if (can('inspecciones.read')): ?>
        <a href="<?= url('inspecciones') ?>" class="nav-link <?= $isActive('inspecciones') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Inspecciones
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (can('danios.read') || can('mantenimiento.read') || can('combustible.read')): ?>
        <div class="nav-section">Operaciones</div>
        <?php if (can('danios.read')): ?>
        <a href="<?= url('danios') ?>" class="nav-link <?= $isActive('danios') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Daños
        </a>
        <?php endif; ?>
        <?php if (can('mantenimiento.read')): ?>
        <a href="<?= url('mantenimiento') ?>" class="nav-link <?= $isActive('mantenimiento') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            Mantenimiento
        </a>
        <?php endif; ?>
        <?php if (can('combustible.read')): ?>
        <a href="<?= url('combustible') ?>" class="nav-link <?= $isActive('combustible') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22h12M5 22V10l5-5 5 5v12M9 22v-4h4v4"/><path d="M17 11h2a2 2 0 0 1 2 2v2a2 2 0 0 0 2 2h0a2 2 0 0 0 2-2V9l-4-4"/></svg>
            Combustible
        </a>
        <?php endif; ?>
        <?php if (can('documentos.read')): ?>
        <a href="<?= url('documentos') ?>" class="nav-link <?= $isActive('documentos') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M13 2v7h7"/></svg>
            Documentos
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (can('alertas.read')): ?>
        <div class="nav-section">Monitoreo</div>
        <a href="<?= url('alertas') ?>" class="nav-link <?= $isActive('alertas') && !str_contains($currentPath, 'config') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            Alertas
        </a>
        <?php if (can('alertas.config')): ?>
        <a href="<?= url('alertas/config') ?>" class="nav-link <?= str_contains($currentPath, 'alertas/config') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Config. alertas
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (can('dashboard.read')): ?>
        <a href="<?= url('reportes') ?>" class="nav-link <?= $isActive('reportes') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
            Reportes
        </a>
        <?php endif; ?>

        <?php if (can('usuarios.read') || can('auditoria.read')): ?>
        <div class="nav-section">Administración</div>
        <?php if (can('usuarios.read')): ?>
        <a href="<?= url('usuarios') ?>" class="nav-link <?= $isActive('usuarios') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Usuarios
        </a>
        <?php endif; ?>
        <?php if (can('auditoria.read')): ?>
        <a href="<?= url('auditoria') ?>" class="nav-link <?= $isActive('auditoria') ? 'active' : '' ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            Auditoría
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>
</aside>
