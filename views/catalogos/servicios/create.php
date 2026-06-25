<?php
$pageTitle = 'Nuevo servicio';
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/servicios') ?>">Servicios</a></li><li>/ Nuevo</li></ul>
        <h1 class="page-title">Registrar servicio</h1>
        <p class="page-subtitle">Aparecerá al registrar mantenimientos y en las alertas por kilometraje</p>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'servicios']); ?>

<div class="card">
    <div class="card-header">
        <h3>Datos del servicio</h3>
    </div>
    <form action="<?= url('catalogos/servicios') ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nombre">Nombre del servicio <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="100"
                       placeholder="Ej. Cambio de aceite" value="<?= e((string) old('nombre')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="tipo">Código interno</label>
                <input type="text" id="tipo" name="tipo" class="form-control" maxlength="50"
                       placeholder="Ej. cambio_aceite (opcional, se genera del nombre)"
                       value="<?= e((string) old('tipo')) ?>">
                <small class="form-hint text-muted">Letras minúsculas, números y guión bajo. No se puede cambiar después.</small>
            </div>
        </div>

        <h4 class="mt-2 mb-1">Umbrales por kilometraje</h4>
        <p class="text-muted mb-2">Kilómetros recorridos desde el último servicio para generar cada nivel de alerta.</p>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="umbral_rojo">Urgente (km) <span class="required">*</span></label>
                <input type="number" id="umbral_rojo" name="umbral_rojo" class="form-control" min="0" required
                       value="<?= e((string) old('umbral_rojo', '500')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="umbral_amarillo">Atención (km) <span class="required">*</span></label>
                <input type="number" id="umbral_amarillo" name="umbral_amarillo" class="form-control" min="0" required
                       value="<?= e((string) old('umbral_amarillo', '2000')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="umbral_verde">Aviso (km) <span class="required">*</span></label>
                <input type="number" id="umbral_verde" name="umbral_verde" class="form-control" min="0" required
                       value="<?= e((string) old('umbral_verde', '5000')) ?>">
            </div>
        </div>

        <details class="mt-2">
            <summary class="text-muted">Umbrales por días (opcional)</summary>
            <p class="text-muted mb-2">Si se configuran, la alerta se activa por kilómetros <em>o</em> por días, lo que ocurra primero.</p>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="umbral_rojo_dias">Urgente (días)</label>
                    <input type="number" id="umbral_rojo_dias" name="umbral_rojo_dias" class="form-control" min="0"
                           value="<?= e((string) old('umbral_rojo_dias', '90')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="umbral_amarillo_dias">Atención (días)</label>
                    <input type="number" id="umbral_amarillo_dias" name="umbral_amarillo_dias" class="form-control" min="0"
                           value="<?= e((string) old('umbral_amarillo_dias', '180')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="umbral_verde_dias">Aviso (días)</label>
                    <input type="number" id="umbral_verde_dias" name="umbral_verde_dias" class="form-control" min="0"
                           value="<?= e((string) old('umbral_verde_dias', '365')) ?>">
                </div>
            </div>
        </details>

        <div class="form-group mt-2">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" checked> Servicio activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Registrar</button>
            <a href="<?= url('catalogos/servicios') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
