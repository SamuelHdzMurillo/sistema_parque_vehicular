<?php
$pageTitle = 'Editar servicio';
$servicio = $servicio ?? [];
$s = array_merge($servicio, array_intersect_key($_SESSION['_old'] ?? [], array_flip([
    'nombre', 'umbral_verde', 'umbral_amarillo', 'umbral_rojo',
    'umbral_verde_dias', 'umbral_amarillo_dias', 'umbral_rojo_dias', 'activo',
])));
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('catalogos') ?>">Catálogos</a></li><li><a href="<?= url('catalogos/servicios') ?>">Servicios</a></li><li>/ Editar</li></ul>
        <h1 class="page-title">Editar servicio</h1>
    </div>
</div>

<?php App\Core\View::component('catalogo-tabs', ['currentTab' => 'servicios']); ?>

<div class="card">
    <div class="card-header">
        <h3><?= e($s['nombre']) ?></h3>
        <span class="badge badge-secondary"><code><?= e($s['tipo']) ?></code></span>
    </div>
    <form action="<?= url('catalogos/servicios/' . $s['id']) ?>" method="post" class="card-body">
        <?= csrf_field() ?>
        <div class="form-group">
            <label class="form-label" for="nombre">Nombre del servicio <span class="required">*</span></label>
            <input type="text" id="nombre" name="nombre" class="form-control" required maxlength="100" value="<?= e($s['nombre']) ?>">
        </div>

        <h4 class="mt-2 mb-1">Umbrales por kilometraje</h4>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="umbral_rojo">Urgente (km) <span class="required">*</span></label>
                <input type="number" id="umbral_rojo" name="umbral_rojo" class="form-control" min="0" required
                       value="<?= (int) $s['umbral_rojo'] ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="umbral_amarillo">Atención (km) <span class="required">*</span></label>
                <input type="number" id="umbral_amarillo" name="umbral_amarillo" class="form-control" min="0" required
                       value="<?= (int) $s['umbral_amarillo'] ?>">
            </div>
            <div class="form-group">
                <label class="form-label" for="umbral_verde">Aviso (km) <span class="required">*</span></label>
                <input type="number" id="umbral_verde" name="umbral_verde" class="form-control" min="0" required
                       value="<?= (int) $s['umbral_verde'] ?>">
            </div>
        </div>

        <details class="mt-2" open>
            <summary class="text-muted">Umbrales por días (opcional)</summary>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="umbral_rojo_dias">Urgente (días)</label>
                    <input type="number" id="umbral_rojo_dias" name="umbral_rojo_dias" class="form-control" min="0"
                           value="<?= e((string) ($s['umbral_rojo_dias'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="umbral_amarillo_dias">Atención (días)</label>
                    <input type="number" id="umbral_amarillo_dias" name="umbral_amarillo_dias" class="form-control" min="0"
                           value="<?= e((string) ($s['umbral_amarillo_dias'] ?? '')) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="umbral_verde_dias">Aviso (días)</label>
                    <input type="number" id="umbral_verde_dias" name="umbral_verde_dias" class="form-control" min="0"
                           value="<?= e((string) ($s['umbral_verde_dias'] ?? '')) ?>">
                </div>
            </div>
        </details>

        <div class="form-group mt-2">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" <?= (int) ($s['activo'] ?? 1) === 1 ? 'checked' : '' ?>> Servicio activo
            </label>
        </div>
        <div class="d-flex gap-1">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= url('catalogos/servicios') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
