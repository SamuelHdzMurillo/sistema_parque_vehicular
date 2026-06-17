<?php
$pageTitle = 'Auditoría';
$data = $data ?? [];
$modulos = auditoria_modulos();
$acciones = auditoria_filtros_accion();
$filtroModulo = $filtro_modulo ?? '';
$filtroAccion = $filtro_accion ?? '';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Auditoría del sistema</h1>
        <p class="page-subtitle">Historial de quién hizo qué, cuándo y en qué parte del sistema</p>
    </div>
</div>
<div class="card">
    <?php App\Core\View::component('auditoria-guia'); ?>

    <form class="filters-bar" method="get" action="<?= url('auditoria') ?>">
        <div class="form-group">
            <label class="form-label" for="modulo">Módulo</label>
            <select id="modulo" name="modulo" class="form-select">
                <option value="">Todos los módulos</option>
                <?php foreach ($modulos as $clave => $etiqueta): ?>
                <option value="<?= e($clave) ?>" <?= $filtroModulo === $clave ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="accion">Tipo de acción</label>
            <select id="accion" name="accion" class="form-select">
                <option value="">Todas las acciones</option>
                <?php foreach ($acciones as $clave => $etiqueta): ?>
                <option value="<?= e($clave) ?>" <?= strtolower($filtroAccion) === strtolower($clave) ? 'selected' : '' ?>><?= e($etiqueta) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-info">Filtrar</button>
        <?php if ($filtroModulo !== '' || $filtroAccion !== ''): ?>
        <a href="<?= url('auditoria') ?>" class="btn btn-secondary">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="table table-auditoria">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Módulo</th>
                    <th>Qué pasó</th>
                    <th>Referencia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        <?php if ($filtroModulo !== '' || $filtroAccion !== ''): ?>
                        No hay registros con los filtros seleccionados.
                        <?php else: ?>
                        Aún no hay movimientos registrados. Cuando alguien cree, modifique o exporte información, aparecerá aquí.
                        <?php endif; ?>
                    </td>
                </tr>
                <?php else: foreach ($data as $log): ?>
                <tr>
                    <td class="text-nowrap"><?= format_datetime($log['created_at'] ?? null) ?></td>
                    <td><?= e($log['usuario'] ?? 'Sistema automático') ?></td>
                    <td>
                        <span class="badge <?= auditoria_accion_badge((string) ($log['accion'] ?? '')) ?>">
                            <?= e(auditoria_accion_label((string) ($log['accion'] ?? ''))) ?>
                        </span>
                    </td>
                    <td><?= e(auditoria_modulo_label((string) ($log['tabla_afectada'] ?? ''))) ?></td>
                    <td class="auditoria-resumen"><?= e(auditoria_resumen($log)) ?></td>
                    <td class="text-muted">
                        <?php if (!empty($log['registro_id'])): ?>
                        #<?= e((string) $log['registro_id']) ?>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php App\Core\View::component('pagination', ['page' => $page ?? 1, 'total' => $total ?? 0, 'per_page' => $per_page ?? 30]); ?>
</div>
