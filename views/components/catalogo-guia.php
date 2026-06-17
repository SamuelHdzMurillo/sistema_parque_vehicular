<?php
$variant = $variant ?? 'general';
?>
<details class="roles-guia catalogos-guia">
    <summary class="roles-guia-summary">
        <div class="roles-guia-summary-text">
            <span class="roles-guia-kicker">Referencia</span>
            <span class="roles-guia-title">¿Cómo funcionan los catálogos?</span>
            <span class="roles-guia-hint">Planteles, áreas y conductores usados en comisiones y formularios</span>
        </div>
        <span class="roles-guia-toggle" aria-hidden="true">
            <svg class="roles-guia-chevron" width="18" height="18" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M4.646 6.646a.5.5 0 0 1 .708 0L8 9.293l2.646-2.647a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 0-.708z"/>
            </svg>
        </span>
    </summary>
    <div class="roles-guia-body">
        <div class="roles-guia-grid catalogos-guia-grid">
            <article class="roles-guia-item catalogos-guia-item catalogos-guia-item--planteles">
                <div class="roles-guia-item-head">
                    <span class="badge badge-primary">Planteles</span>
                    <span class="roles-guia-nivel">Paso 1</span>
                </div>
                <p class="roles-guia-item-text">Sedes o campus del organismo: DG, LP, CAB, etc. Son la base para agrupar áreas.</p>
            </article>
            <article class="roles-guia-item catalogos-guia-item catalogos-guia-item--areas">
                <div class="roles-guia-item-head">
                    <span class="badge badge-info">Áreas</span>
                    <span class="roles-guia-nivel">Paso 2</span>
                </div>
                <p class="roles-guia-item-text">Departamentos o unidades de cada plantel. En comisiones se muestran como <strong>Jurídico - DG</strong> (área + clave del plantel).</p>
            </article>
            <article class="roles-guia-item catalogos-guia-item catalogos-guia-item--conductores">
                <div class="roles-guia-item-head">
                    <span class="badge badge-success">Conductores</span>
                    <span class="roles-guia-nivel">Paso 3</span>
                </div>
                <p class="roles-guia-item-text">Personas autorizadas para conducir. Cada conductor tiene nombre, área de adscripción y teléfono de contacto.</p>
            </article>
        </div>
        <?php if ($variant === 'general'): ?>
        <p class="catalogos-guia-foot text-muted">Registre primero planteles, luego áreas y después conductores. Los cambios se reflejan de inmediato en comisiones y demás módulos.</p>
        <?php endif; ?>
    </div>
</details>
