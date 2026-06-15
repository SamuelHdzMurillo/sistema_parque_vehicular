<?php $pageTitle = 'Página no encontrada'; http_response_code(404); ?>
<div class="error-page">
    <p class="error-code">404</p>
    <h1>Página no encontrada</h1>
    <p class="error-message">La ruta solicitada no existe o no tiene permisos para acceder.</p>
    <a href="<?= url('dashboard') ?>" class="btn btn-primary">Ir al dashboard</a>
</div>
