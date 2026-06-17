<?php
$currentTab = $currentTab ?? 'inicio';
$tabs = [
    'inicio' => ['url' => 'catalogos', 'label' => 'Inicio'],
    'planteles' => ['url' => 'catalogos/planteles', 'label' => 'Planteles'],
    'areas' => ['url' => 'catalogos/areas', 'label' => 'Áreas'],
    'conductores' => ['url' => 'catalogos/conductores', 'label' => 'Conductores'],
];
?>
<div class="card catalogos-nav mb-2">
    <div class="card-body catalogos-nav-body">
        <nav class="tabs catalogos-tabs" aria-label="Secciones de catálogos">
            <?php foreach ($tabs as $key => $tab): ?>
            <a href="<?= url($tab['url']) ?>" class="tab-btn<?= $currentTab === $key ? ' active' : '' ?>">
                <?= e($tab['label']) ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
