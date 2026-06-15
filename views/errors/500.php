<?php $pageTitle = 'Error del servidor'; http_response_code(500); ?>
<div class="error-page">
    <p class="error-code">500</p>
    <h1>Error interno del servidor</h1>
    <p class="error-message">Ocurrió un problema inesperado. El equipo técnico ha sido notificado. Intente de nuevo más tarde.</p>
    <a href="<?= url('dashboard') ?>" class="btn btn-primary">Ir al inicio</a>
</div>
