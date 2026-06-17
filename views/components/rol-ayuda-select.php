<div id="rol-ayuda" class="rol-ayuda" hidden>
    <div class="rol-ayuda-inner">
        <div class="rol-ayuda-head">
            <span id="rol-ayuda-badge" class="badge badge-secondary"></span>
            <span id="rol-ayuda-nivel" class="roles-guia-nivel"></span>
        </div>
        <p id="rol-ayuda-texto" class="rol-ayuda-texto"></p>
    </div>
</div>
<script>
(function () {
    const select = document.getElementById('role_id');
    const panel = document.getElementById('rol-ayuda');
    const badge = document.getElementById('rol-ayuda-badge');
    const nivel = document.getElementById('rol-ayuda-nivel');
    const texto = document.getElementById('rol-ayuda-texto');
    if (!select || !panel || !badge || !nivel || !texto) {
        return;
    }

    const badgeClasses = {
        admin_general: 'badge-primary',
        admin_transporte: 'badge-success',
        supervisor: 'badge-info',
        responsable_vehiculo: 'badge-warning',
        consulta: 'badge-secondary'
    };

    const nivelLabels = {
        admin_general: 'Acceso total',
        admin_transporte: 'Operación del parque',
        supervisor: 'Supervisión y autorización',
        responsable_vehiculo: 'Operación en campo',
        consulta: 'Solo consulta'
    };

    function actualizar() {
        const option = select.options[select.selectedIndex];
        const descripcion = option ? option.getAttribute('data-descripcion') : '';
        const slug = option ? option.getAttribute('data-slug') : '';
        const inner = panel.querySelector('.rol-ayuda-inner');

        if (!descripcion) {
            panel.hidden = true;
            return;
        }

        badge.textContent = option.textContent.trim();
        badge.className = 'badge ' + (badgeClasses[slug] || 'badge-secondary');
        nivel.textContent = nivelLabels[slug] || '';
        texto.textContent = descripcion;
        if (inner) {
            inner.className = 'rol-ayuda-inner' + (slug ? ' rol-ayuda-inner--' + slug : '');
        }
        panel.hidden = false;
    }

    select.addEventListener('change', actualizar);
    actualizar();
})();
</script>
