<?php
$pageTitle = 'Búsqueda global';
$q = $q ?? '';
$results = $results ?? [];
$tipoLabels = ['vehiculo' => 'Vehículos', 'comision' => 'Comisiones', 'usuario' => 'Usuarios'];
$tipoUrls = [
    'vehiculo' => static fn($id) => url('vehiculos/' . $id),
    'comision' => static fn($id) => url('comisiones/' . $id),
    'usuario' => static fn($id) => url('usuarios/' . $id . '/edit'),
];
$grouped = [];
foreach ($results as $r) {
    $tipo = $r['tipo'] ?? 'otro';
    $grouped[$tipo][] = $r;
}
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Búsqueda global</h1>
        <p class="page-subtitle">Encuentre vehículos, comisiones y usuarios en todo el sistema</p>
    </div>
</div>

<div class="card mb-2">
    <form class="card-body" method="get" action="<?= url('busqueda') ?>">
        <div class="input-group">
            <input type="search" name="q" class="form-control" placeholder="Escriba al menos 2 caracteres…"
                   value="<?= e($q) ?>" autofocus required minlength="2">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </div>
    </form>
</div>

<?php if ($q !== '' && strlen($q) >= 2): ?>
    <?php if (empty($results)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <p>No se encontraron resultados para «<?= e($q) ?>»</p>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($grouped as $tipo => $items): ?>
        <div class="search-results-group">
            <h3><?= e($tipoLabels[$tipo] ?? ucfirst($tipo)) ?> (<?= count($items) ?>)</h3>
            <?php foreach ($items as $item): ?>
            <?php $href = isset($tipoUrls[$tipo]) ? $tipoUrls[$tipo]($item['id']) : '#'; ?>
            <a href="<?= e($href) ?>" class="search-result-item">
                <strong><?= e($item['titulo'] ?? '') ?></strong>
                <small><?= e($item['subtitulo'] ?? '') ?></small>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php else: ?>
<div class="card">
    <div class="empty-state">
        <p class="text-muted">Use la barra de búsqueda superior o el formulario para iniciar una consulta.</p>
        <p class="text-muted"><kbd>Ctrl</kbd> + <kbd>K</kbd> abre la búsqueda rápida desde cualquier pantalla.</p>
    </div>
</div>
<?php endif; ?>
