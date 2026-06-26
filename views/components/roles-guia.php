<?php
/** @var array<int, array<string, mixed>> $roles */
$roles = $roles ?? [];
if ($roles === []) {
    return;
}
?>
<details class="roles-guia">
    <summary class="roles-guia-summary">
        <div class="roles-guia-summary-text">
            <span class="roles-guia-kicker">Referencia</span>
            <span class="roles-guia-title">¿Qué hace cada rol?</span>
            <span class="roles-guia-hint">Permisos y responsabilidades de cada perfil de acceso</span>
        </div>
        <span class="roles-guia-toggle" aria-hidden="true">
            <svg class="roles-guia-chevron" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M4.646 6.646a.5.5 0 0 1 .708 0L8 9.293l2.646-2.647a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 0-.708z"/>
            </svg>
        </span>
    </summary>
    <div class="roles-guia-body">
        <div class="roles-guia-grid">
            <?php foreach ($roles as $r):
                $slug = (string) ($r['slug'] ?? '');
            ?>
            <article class="roles-guia-item roles-guia-item--<?= e($slug) ?>" id="rol-<?= e($slug) ?>">
                <div class="roles-guia-item-head">
                    <span class="badge <?= rol_badge_class($slug) ?>"><?= e((string) ($r['nombre'] ?? '')) ?></span>
                    <?php if (rol_nivel_label($slug) !== ''): ?>
                    <span class="roles-guia-nivel"><?= e(rol_nivel_label($slug)) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($r['descripcion'])): ?>
                <p class="roles-guia-item-text"><?= e((string) $r['descripcion']) ?></p>
                <?php else: ?>
                <p class="roles-guia-item-text text-muted">Sin descripción disponible.</p>
                <?php endif; ?>
                <?php if (!empty($r['permisos_resumen'])): ?>
                <p class="roles-guia-permisos-resumen"><?= e((string) $r['permisos_resumen']) ?></p>
                <?php endif; ?>
                <?php if (!empty($r['permisos_grupos'])): ?>
                <details class="roles-guia-permisos">
                    <summary>Ver permisos de este rol</summary>
                    <div class="roles-guia-permisos-body">
                        <?php App\Core\View::component('usuario-permisos', [
                            'permisos_grupos' => $r['permisos_grupos'],
                            'compacto' => true,
                        ]); ?>
                    </div>
                </details>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</details>
