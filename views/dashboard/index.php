<?php
$pageTitle = 'Dashboard';
$kpis = $kpis ?? [];
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Panel de control</h1>
        <p class="page-subtitle">Resumen operativo del parque vehicular — <?= e((string) config('app', 'institution')) ?></p>
    </div>
    <div class="page-actions">
        <?php if (can('vehiculos.create')): ?>
        <a href="<?= url('vehiculos/create') ?>" class="btn btn-primary">+ Nuevo vehículo</a>
        <?php endif; ?>
        <?php if (can('comisiones.create')): ?>
        <a href="<?= url('comisiones/create') ?>" class="btn btn-accent">+ Nueva comisión</a>
        <?php endif; ?>
    </div>
</div>

<div class="kpi-grid">
    <div class="kpi-card primary">
        <div class="kpi-label">Total vehículos</div>
        <div class="kpi-value"><?= (int) ($kpis['vehiculos_total'] ?? 0) ?></div>
    </div>
    <div class="kpi-card success">
        <div class="kpi-label">Operativos</div>
        <div class="kpi-value"><?= (int) ($kpis['vehiculos_operativos'] ?? 0) ?></div>
    </div>
    <div class="kpi-card info">
        <div class="kpi-label">En comisión</div>
        <div class="kpi-value"><?= (int) ($kpis['vehiculos_en_comision'] ?? 0) ?></div>
    </div>
    <div class="kpi-card warning">
        <div class="kpi-label">En mantenimiento</div>
        <div class="kpi-value"><?= (int) ($kpis['vehiculos_en_mantenimiento'] ?? 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Comisiones activas</div>
        <div class="kpi-value"><?= (int) ($kpis['comisiones_activas'] ?? 0) ?></div>
    </div>
    <div class="kpi-card danger">
        <div class="kpi-label">Alertas rojas</div>
        <div class="kpi-value"><?= (int) ($kpis['alertas_rojas'] ?? 0) ?></div>
    </div>
    <div class="kpi-card warning">
        <div class="kpi-label">Alertas amarillas</div>
        <div class="kpi-value"><?= (int) ($kpis['alertas_amarillas'] ?? 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Alertas pendientes</div>
        <div class="kpi-value"><?= (int) ($kpis['alertas_pendientes'] ?? 0) ?></div>
    </div>
    <div class="kpi-card primary">
        <div class="kpi-label">Costo total parque</div>
        <div class="kpi-value" style="font-size:1.35rem"><?= format_money($kpis['costo_total_parque'] ?? 0) ?></div>
    </div>
</div>

<div class="chart-grid">
    <div class="card">
        <div class="card-header">
            <h3>Estado del parque</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chartEstadoParque"></canvas>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3>Alertas por nivel</h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chartAlertas"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#b0b0b0' : '#6c757d';
    const gridColor = isDark ? '#444444' : '#dee2e6';

    new Chart(document.getElementById('chartEstadoParque'), {
        type: 'doughnut',
        data: {
            labels: ['Operativos', 'En comisión', 'En mantenimiento', 'Otros'],
            datasets: [{
                data: [
                    <?= (int) ($kpis['vehiculos_operativos'] ?? 0) ?>,
                    <?= (int) ($kpis['vehiculos_en_comision'] ?? 0) ?>,
                    <?= (int) ($kpis['vehiculos_en_mantenimiento'] ?? 0) ?>,
                    <?= max(0, (int) ($kpis['vehiculos_total'] ?? 0) - (int) ($kpis['vehiculos_operativos'] ?? 0)) ?>
                ],
                backgroundColor: ['#4CAF50', '#F27022', '#f0ad4e', '#adb5bd'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { color: textColor } } }
        }
    });

    new Chart(document.getElementById('chartAlertas'), {
        type: 'bar',
        data: {
            labels: ['Rojas', 'Amarillas', 'Pendientes'],
            datasets: [{
                label: 'Alertas',
                data: [
                    <?= (int) ($kpis['alertas_rojas'] ?? 0) ?>,
                    <?= (int) ($kpis['alertas_amarillas'] ?? 0) ?>,
                    <?= (int) ($kpis['alertas_pendientes'] ?? 0) ?>
                ],
                backgroundColor: ['#dc3545', '#f0ad4e', '#F27022'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { color: textColor, stepSize: 1 }, grid: { color: gridColor } },
                x: { ticks: { color: textColor }, grid: { display: false } }
            }
        }
    });
});
</script>
