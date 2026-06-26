<?php
$permisos_por_rol = $permisos_por_rol ?? [];
$roleIdInicial = isset($role_id_inicial) ? (int) $role_id_inicial : 0;
?>
<div id="rol-permisos-preview" class="rol-permisos-preview" hidden>
    <div class="rol-permisos-preview-head">
        <span class="rol-permisos-preview-kicker">Permisos incluidos en este rol</span>
        <p id="rol-permisos-preview-resumen" class="rol-permisos-preview-resumen"></p>
    </div>
    <div id="rol-permisos-preview-body" class="rol-permisos-preview-body"></div>
</div>
<script type="application/json" id="permisos-por-rol-data"><?= json_encode(
    permiso_json_por_rol($permisos_por_rol),
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
) ?></script>
<script>
(function () {
    const select = document.getElementById('role_id');
    const panel = document.getElementById('rol-permisos-preview');
    const resumen = document.getElementById('rol-permisos-preview-resumen');
    const body = document.getElementById('rol-permisos-preview-body');
    const dataEl = document.getElementById('permisos-por-rol-data');
    if (!select || !panel || !resumen || !body || !dataEl) {
        return;
    }

    let permisosPorRol = {};
    try {
        permisosPorRol = JSON.parse(dataEl.textContent || '{}');
    } catch (e) {
        return;
    }

    function contarPermisos(grupos) {
        return (grupos || []).reduce(function (total, grupo) {
            return total + ((grupo.permisos || []).length);
        }, 0);
    }

    function resumenTexto(total, grupos) {
        if (total === 0) {
            return 'Este rol no tiene permisos asignados.';
        }
        return 'Puede realizar ' + total + ' acción' + (total === 1 ? '' : 'es') + ' en ' + grupos.length + ' área' + (grupos.length === 1 ? '' : 's') + ' del sistema.';
    }

    function renderPermisos(grupos) {
        body.innerHTML = '';
        if (!grupos || grupos.length === 0) {
            body.innerHTML = '<p class="text-muted">Sin permisos para mostrar.</p>';
            return;
        }

        const grid = document.createElement('div');
        grid.className = 'usuario-permisos-grid';

        grupos.forEach(function (grupo) {
            const section = document.createElement('section');
            section.className = 'usuario-permisos-modulo';

            const title = document.createElement('h4');
            title.className = 'usuario-permisos-modulo-title';
            title.textContent = grupo.label || grupo.modulo || 'General';
            section.appendChild(title);

            const list = document.createElement('ul');
            list.className = 'usuario-permisos-lista';

            (grupo.permisos || []).forEach(function (perm) {
                const item = document.createElement('li');
                item.className = 'usuario-permisos-item';
                item.innerHTML = '<span class="usuario-permisos-check" aria-hidden="true">✓</span><span></span>';
                item.querySelector('span:last-child').textContent = perm.texto || perm.descripcion || perm.slug || 'Permiso';
                list.appendChild(item);
            });

            section.appendChild(list);
            grid.appendChild(section);
        });

        body.appendChild(grid);
    }

    function actualizar() {
        const roleId = select.value;
        const grupos = permisosPorRol[roleId] || [];
        const total = contarPermisos(grupos);

        if (!roleId) {
            panel.hidden = true;
            return;
        }

        resumen.textContent = resumenTexto(total, grupos);
        renderPermisos(grupos);
        panel.hidden = false;
    }

    select.addEventListener('change', actualizar);
    actualizar();
})();
</script>
