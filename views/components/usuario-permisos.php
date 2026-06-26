<?php
$permisos_grupos = $permisos_grupos ?? permiso_agrupar_por_modulo($permisos ?? []);
$compacto = !empty($compacto);
$totalPermisos = 0;
foreach ($permisos_grupos as $grupo) {
    $totalPermisos += count($grupo['permisos'] ?? []);
}
if ($totalPermisos === 0): ?>
<p class="usuario-permisos-vacio text-muted">Este rol no tiene permisos asignados.</p>
<?php return; endif; ?>
<div class="usuario-permisos<?= $compacto ? ' usuario-permisos--compacto' : '' ?>">
    <p class="usuario-permisos-intro">
        Puede realizar <strong><?= $totalPermisos ?></strong> acción<?= $totalPermisos === 1 ? '' : 'es' ?> en el sistema, organizadas por área:
    </p>
    <div class="usuario-permisos-grid">
        <?php foreach ($permisos_grupos as $grupo): ?>
        <section class="usuario-permisos-modulo">
            <h4 class="usuario-permisos-modulo-title"><?= e($grupo['label']) ?></h4>
            <ul class="usuario-permisos-lista">
                <?php foreach ($grupo['permisos'] as $perm): ?>
                <li class="usuario-permisos-item">
                    <span class="usuario-permisos-check" aria-hidden="true">✓</span>
                    <span><?= e($perm['texto'] ?? permiso_texto_amigable($perm)) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endforeach; ?>
    </div>
</div>
