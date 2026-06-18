<?php
$pageTitle = 'Comisión ' . ($comision['folio'] ?? '');
$c = $comision ?? [];
$um = $ultimo_mantenimiento ?? null;
$lucesTablero = $luces_tablero ?? [];
$lucesById = [];
foreach ($lucesTablero as $luz) {
    $lucesById[$luz['codigo']] = $luz;
}
$liquidos = $liquidos ?? [];
$nivelOpciones = $nivel_opciones ?? [];
$estados = ['borrador' => 'Borrador', 'en_curso' => 'En curso', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada'];
$minKmRegreso = max((int) ($c['km_salida'] ?? 0), (int) ($c['kilometraje_actual'] ?? 0));

$showAcciones = ($c['estado'] === 'borrador' && can('comisiones.update'))
    || (in_array($c['estado'], ['borrador', 'en_curso'], true) && can('comisiones.delete'));

$defaultTab = 'resumen';
if ($c['estado'] === 'en_curso' && can('comisiones.update')) {
    $defaultTab = 'regreso';
} elseif ($c['estado'] === 'borrador' && can('comisiones.update')) {
    $defaultTab = 'acciones';
}

$renderNiveles = static function (array $niveles) use ($liquidos, $nivelOpciones): string {
    if ($niveles === []) {
        return '<p class="text-muted" style="margin:0">Sin registro de niveles.</p>';
    }
    $html = '<div class="meta-grid">';
    foreach ($liquidos as $liq) {
        $cod = $liq['codigo'];
        if (!isset($niveles[$cod])) {
            continue;
        }
        $txt = $nivelOpciones[$niveles[$cod]] ?? $niveles[$cod];
        $html .= '<div class="meta-item"><label>' . e($liq['nombre']) . '</label><span>' . e($txt) . '</span></div>';
    }
    $html .= '</div>';
    return $html;
};

$renderLuces = static function (array $codigos) use ($lucesById): string {
    if ($codigos === []) {
        return '<p class="text-muted" style="margin:0">No tiene luces prendidas.</p>';
    }
    $html = '<div class="dash-lights-grid">';
    foreach ($codigos as $codigo) {
        $luz = $lucesById[$codigo] ?? null;
        if ($luz === null) {
            continue;
        }
        $html .= '<div class="dash-light-card is-on" style="cursor:default">'
            . '<span class="dash-light-icon" aria-hidden="true"><img src="' . e(asset('images/luces-tablero/' . $luz['icon'])) . '" alt="" width="48" height="48"></span>'
            . '<span class="dash-light-name">' . e($luz['nombre']) . '</span>'
            . '<span class="dash-light-status">Encendida</span>'
            . '</div>';
    }
    $html .= '</div>';
    return $html;
};
?>
<div class="page-header">
    <div>
        <ul class="breadcrumb"><li><a href="<?= url('comisiones') ?>">Comisiones</a></li><li>/ <?= e($c['folio']) ?></li></ul>
        <h1 class="page-title">Comisión <?= e($c['folio']) ?></h1>
        <p class="page-subtitle">
            <span class="badge badge-secondary"><?= e($estados[$c['estado']] ?? $c['estado']) ?></span>
            — Vehículo <?= e($c['numero_economico'] ?? '—') ?>
        </p>
    </div>
    <div class="page-actions">
        <a href="<?= url('formatos/comision/' . $c['id'] . '?parte=salida') ?>" class="btn btn-secondary" target="_blank">Imprimir salida</a>
        <a href="<?= url('formatos/comision/' . $c['id'] . '?parte=regreso') ?>" class="btn btn-secondary" target="_blank">Imprimir regreso</a>
        <a href="<?= url('formatos/comision/' . $c['id']) ?>" class="btn btn-secondary" target="_blank">Imprimir completo</a>
        <?php if (can('comisiones.update') && in_array($c['estado'], ['borrador', 'en_curso'], true)): ?>
        <a href="<?= url('comisiones/' . $c['id'] . '/edit') ?>" class="btn btn-secondary">Editar</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body" data-tabs data-default-tab="<?= e($defaultTab) ?>">
        <div class="tabs">
            <button type="button" class="tab-btn<?= $defaultTab === 'resumen' ? ' active' : '' ?>" data-tab="resumen">Resumen</button>
            <button type="button" class="tab-btn<?= $defaultTab === 'salida' ? ' active' : '' ?>" data-tab="salida">Salida</button>
            <button type="button" class="tab-btn<?= $defaultTab === 'regreso' ? ' active' : '' ?>" data-tab="regreso">Regreso</button>
            <button type="button" class="tab-btn<?= $defaultTab === 'documentos' ? ' active' : '' ?>" data-tab="documentos">Documentos</button>
            <?php if ($showAcciones): ?>
            <button type="button" class="tab-btn<?= $defaultTab === 'acciones' ? ' active' : '' ?>" data-tab="acciones">Acciones</button>
            <?php endif; ?>
        </div>

        <!-- Resumen -->
        <div id="tab-resumen" class="tab-panel<?= $defaultTab === 'resumen' ? ' active' : '' ?>">
            <div class="meta-grid">
                <div class="meta-item"><label>Fecha</label><span><?= format_date($c['fecha']) ?></span></div>
                <div class="meta-item"><label>Conductor</label><span><?= e($c['conductor_nombre']) ?><?php if (!empty($c['conductor_telefono'])): ?> <span class="text-muted">(<?= e($c['conductor_telefono']) ?>)</span><?php endif; ?></span></div>
                <div class="meta-item"><label>Área</label><span><?= e($c['area_solicitante_nombre'] ?? $c['area_nombre'] ?? '—') ?></span></div>
                <div class="meta-item"><label>Responsable</label><span><?= e($c['responsable_nombre'] ?? '—') ?></span></div>
                <div class="meta-item"><label>Responsable de regreso</label><span><?= e($c['responsable_regreso_nombre'] ?? '—') ?: '—' ?></span></div>
                <div class="meta-item"><label>Km recorridos</label><span><?= $c['km_recorridos'] !== null ? number_format((int) $c['km_recorridos']) : '—' ?></span></div>
                <div class="meta-item"><label>Rendimiento</label><span><?= $c['rendimiento'] !== null ? number_format((float) $c['rendimiento'], 2) . ' km/L' : '—' ?></span></div>
            </div>
            <p class="mt-2"><strong>Destino:</strong> <?= e($c['destino']) ?></p>
            <p><strong>Motivo:</strong> <?= e($c['motivo']) ?></p>
            <?php if (!empty($c['observaciones'])): ?>
            <p><strong>Observaciones:</strong> <?= e($c['observaciones']) ?></p>
            <?php endif; ?>

            <hr style="margin:1.25rem 0;border:none;border-top:1px solid var(--border-color)">

            <h3 style="margin:0 0 .75rem;font-size:1rem">Último mantenimiento del vehículo</h3>
            <?php if ($um !== null): ?>
            <div class="meta-grid">
                <div class="meta-item"><label>Folio</label><span><?= e($um['folio'] ?? '—') ?></span></div>
                <div class="meta-item"><label>Fecha</label><span><?= format_date($um['fecha'] ?? null) ?></span></div>
                <div class="meta-item"><label>Tipo</label><span><?= e(ucfirst((string) ($um['tipo'] ?? '—'))) ?></span></div>
                <div class="meta-item"><label>Kilometraje</label><span><?= isset($um['kilometraje']) ? number_format((int) $um['kilometraje']) . ' km' : '—' ?></span></div>
                <div class="meta-item"><label>Proveedor</label><span><?= e($um['proveedor_nombre'] ?? '—') ?: '—' ?></span></div>
            </div>
            <?php if (!empty($um['descripcion'])): ?>
            <p class="mt-2"><strong>Descripción:</strong> <?= e($um['descripcion']) ?></p>
            <?php endif; ?>
            <?php else: ?>
            <p class="text-muted">Sin mantenimientos finalizados registrados para este vehículo.</p>
            <?php endif; ?>
        </div>

        <!-- Salida -->
        <div id="tab-salida" class="tab-panel<?= $defaultTab === 'salida' ? ' active' : '' ?>">
            <div class="meta-grid">
                <div class="meta-item"><label>Hora salida</label><span><?= e(substr($c['hora_salida'] ?? '', 0, 5)) ?></span></div>
                <div class="meta-item"><label>Km salida</label><span><?= number_format((int) $c['km_salida']) ?></span></div>
                <div class="meta-item"><label>Combustible</label><span><?= e(combustible_porcentaje_a_fraccion($c['combustible_salida'] ?? null)) ?></span></div>
            </div>

            <h4 style="margin:1.25rem 0 .5rem;font-size:.9rem">Luces del tablero encendidas</h4>
            <?= $renderLuces($c['luces_salida'] ?? []) ?>

            <h4 style="margin:1.25rem 0 .5rem;font-size:.9rem">Niveles de líquidos</h4>
            <?= $renderNiveles($c['niveles_salida'] ?? []) ?>

            <?php if (!empty($c['doc_salida_ruta'])): ?>
            <p class="mt-2">
                <a href="<?= e(url('storage/uploads/' . ltrim($c['doc_salida_ruta'], '/'))) ?>" target="_blank" class="btn btn-sm btn-secondary">Ver PDF de salida cargado</a>
            </p>
            <?php endif; ?>
        </div>

        <!-- Regreso -->
        <div id="tab-regreso" class="tab-panel<?= $defaultTab === 'regreso' ? ' active' : '' ?>">
            <?php if ($c['estado'] === 'finalizada' || $c['hora_regreso'] !== null): ?>
            <div class="meta-grid">
                <div class="meta-item"><label>Hora regreso</label><span><?= $c['hora_regreso'] ? e(substr($c['hora_regreso'], 0, 5)) : '—' ?></span></div>
                <div class="meta-item"><label>Km regreso</label><span><?= $c['km_regreso'] !== null ? number_format((int) $c['km_regreso']) : '—' ?></span></div>
                <div class="meta-item"><label>Combustible</label><span><?= $c['combustible_regreso'] !== null ? e(combustible_porcentaje_a_fraccion($c['combustible_regreso'])) : '—' ?></span></div>
            </div>

            <h4 style="margin:1.25rem 0 .5rem;font-size:.9rem">Luces del tablero encendidas</h4>
            <?= $renderLuces($c['luces_regreso'] ?? []) ?>

            <h4 style="margin:1.25rem 0 .5rem;font-size:.9rem">Niveles de líquidos</h4>
            <?= $renderNiveles($c['niveles_regreso'] ?? []) ?>

            <?php if (!empty($c['firma_digital'])): ?>
            <h4 style="margin:1.25rem 0 .5rem;font-size:.9rem">Firma digital del conductor</h4>
            <img src="<?= e(url('storage/uploads/' . ltrim($c['firma_digital'], '/'))) ?>" alt="Firma" style="max-width:320px;border:1px solid var(--border-color);border-radius:8px">
            <?php endif; ?>

            <?php if (!empty($c['doc_regreso_ruta'])): ?>
            <p class="mt-2">
                <a href="<?= e(url('storage/uploads/' . ltrim($c['doc_regreso_ruta'], '/'))) ?>" target="_blank" class="btn btn-sm btn-secondary">Ver PDF de regreso cargado</a>
            </p>
            <?php endif; ?>
            <?php elseif ($c['estado'] === 'borrador'): ?>
            <p class="text-muted">La comisión aún no ha iniciado. Registre la salida en la pestaña <strong>Acciones</strong> para poder capturar el regreso.</p>
            <?php else: ?>
            <p class="text-muted">El vehículo está en comisión. Complete el formulario siguiente al regresar.</p>
            <?php endif; ?>

            <?php if ($c['estado'] === 'en_curso' && can('comisiones.update')): ?>
            <hr style="margin:1.5rem 0;border:none;border-top:1px solid var(--border-color)">
            <h3 style="margin:0 0 1rem;font-size:1rem">Finalizar comisión</h3>
            <form action="<?= url('comisiones/' . $c['id'] . '/finalizar') ?>" method="post">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="hora_regreso">Hora regreso <span class="required">*</span></label>
                        <input type="time" id="hora_regreso" name="hora_regreso" class="form-control" required value="<?= e((string) old('hora_regreso', date('H:i'))) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="km_regreso">Km regreso <span class="required">*</span></label>
                        <input type="number" id="km_regreso" name="km_regreso" class="form-control" required min="<?= $minKmRegreso ?>" value="<?= e((string) old('km_regreso', '')) ?>">
                        <small class="form-hint text-muted">Mínimo: <?= number_format($minKmRegreso) ?> km (salida: <?= number_format((int) $c['km_salida']) ?>, odómetro actual: <?= number_format((int) ($c['kilometraje_actual'] ?? 0)) ?>).</small>
                    </div>
                    <div class="form-group">
                        <?php App\Core\View::component('combustible-fraccion-select', [
                            'id' => 'combustible_regreso',
                            'name' => 'combustible_regreso',
                            'label' => 'Combustible regreso',
                            'required' => true,
                        ]); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="observaciones_fin">Observaciones de regreso</label>
                    <textarea id="observaciones_fin" name="observaciones" class="form-textarea"><?= e((string) old('observaciones', '')) ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Luces del tablero encendidas (al regreso)</label>
                    <p class="card-header-hint">Marque las luces de advertencia encendidas al momento del regreso. Si no hay ninguna, déjelas apagadas.</p>
                    <?php $lucesRegreso = $c['luces_regreso'] ?? []; ?>
                    <div class="dash-lights-grid" data-dash-lights>
                        <?php foreach ($lucesTablero as $luz): ?>
                        <?php $codigo = $luz['codigo']; $isOn = in_array($codigo, $lucesRegreso, true); ?>
                        <label class="dash-light-card<?= $isOn ? ' is-on' : '' ?>">
                            <input type="checkbox" name="luces_regreso[]" value="<?= e($codigo) ?>" <?= $isOn ? 'checked' : '' ?>>
                            <span class="dash-light-icon" aria-hidden="true">
                                <img src="<?= e(asset('images/luces-tablero/' . $luz['icon'])) ?>" alt="" width="48" height="48">
                            </span>
                            <span class="dash-light-name"><?= e($luz['nombre']) ?></span>
                            <span class="dash-light-status"><?= $isOn ? 'Encendida' : 'Apagada' ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="dash-lights-summary mt-2" data-dash-lights-summary>
                        <span data-dash-lights-count><?= count($lucesRegreso) ?></span> luz(es) seleccionada(s)
                    </p>
                </div>
                <div class="form-group">
                    <label class="form-label">Niveles de líquidos (al regreso)</label>
                    <?php $nivelesRegreso = $c['niveles_regreso'] ?? []; ?>
                    <div class="checklist-grid">
                        <?php foreach ($liquidos as $liq): ?>
                        <?php $cod = $liq['codigo']; $sel = (string) ($nivelesRegreso[$cod] ?? 'lleno'); ?>
                        <div class="checklist-item">
                            <div class="checklist-item-name"><?= e($liq['nombre']) ?></div>
                            <div class="rating-group">
                                <label class="rating-bueno">
                                    <input type="radio" name="niveles_regreso[<?= e($cod) ?>]" value="lleno" <?= $sel === 'lleno' ? 'checked' : '' ?>>
                                    <span>Lleno</span>
                                </label>
                                <label class="rating-regular">
                                    <input type="radio" name="niveles_regreso[<?= e($cod) ?>]" value="medio" <?= $sel === 'medio' ? 'checked' : '' ?>>
                                    <span>Medio</span>
                                </label>
                                <label class="rating-malo">
                                    <input type="radio" name="niveles_regreso[<?= e($cod) ?>]" value="bajo" <?= $sel === 'bajo' ? 'checked' : '' ?>>
                                    <span>Bajo</span>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Firma digital del conductor (regreso)</label>
                    <div class="signature-pad-wrapper" data-signature-pad>
                        <canvas></canvas>
                        <div class="signature-actions">
                            <button type="button" class="btn btn-sm btn-secondary" data-signature-clear>Limpiar firma</button>
                        </div>
                        <input type="hidden" name="firma_data" value="">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Finalizar comisión</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Documentos -->
        <div id="tab-documentos" class="tab-panel<?= $defaultTab === 'documentos' ? ' active' : '' ?>">
            <p class="card-header-hint" style="margin-top:0">Cargue el PDF firmado de salida y el de regreso una vez impresos y firmados.</p>

            <div class="card mb-2" style="border:1px solid var(--border-color)">
                <div class="card-header"><h3 style="margin:0;font-size:.95rem">Documento de salida (PDF firmado)</h3></div>
                <div class="card-body">
                    <?php if (!empty($c['doc_salida_ruta'])): ?>
                    <p class="mb-2"><a href="<?= e(url('storage/uploads/' . ltrim($c['doc_salida_ruta'], '/'))) ?>" target="_blank">Ver documento de salida cargado</a></p>
                    <?php else: ?>
                    <p class="text-muted mb-2">Aún no se ha cargado el documento de salida.</p>
                    <?php endif; ?>
                    <?php if (can('comisiones.update')): ?>
                    <form action="<?= url('comisiones/' . $c['id'] . '/documento') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tipo" value="salida">
                        <input type="file" name="archivo" class="form-control mb-1" accept="application/pdf" required>
                        <button type="submit" class="btn btn-sm btn-primary"><?= !empty($c['doc_salida_ruta']) ? 'Reemplazar salida' : 'Cargar salida' ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="border:1px solid var(--border-color)">
                <div class="card-header"><h3 style="margin:0;font-size:.95rem">Documento de regreso (PDF firmado)</h3></div>
                <div class="card-body">
                    <?php if (!empty($c['doc_regreso_ruta'])): ?>
                    <p class="mb-2"><a href="<?= e(url('storage/uploads/' . ltrim($c['doc_regreso_ruta'], '/'))) ?>" target="_blank">Ver documento de regreso cargado</a></p>
                    <?php else: ?>
                    <p class="text-muted mb-2">Aún no se ha cargado el documento de regreso.</p>
                    <?php endif; ?>
                    <?php if (can('comisiones.update')): ?>
                    <form action="<?= url('comisiones/' . $c['id'] . '/documento') ?>" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="tipo" value="regreso">
                        <input type="file" name="archivo" class="form-control mb-1" accept="application/pdf" required>
                        <button type="submit" class="btn btn-sm btn-primary"><?= !empty($c['doc_regreso_ruta']) ? 'Reemplazar regreso' : 'Cargar regreso' ?></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($showAcciones): ?>
        <!-- Acciones -->
        <div id="tab-acciones" class="tab-panel<?= $defaultTab === 'acciones' ? ' active' : '' ?>">
            <?php if ($c['estado'] === 'borrador' && can('comisiones.update')): ?>
            <div class="card mb-2" style="border:1px solid var(--border-color)">
                <div class="card-header"><h3 style="margin:0;font-size:.95rem">Iniciar comisión</h3></div>
                <div class="card-body">
                    <p class="text-muted">Al iniciar, el vehículo pasará a estado «En comisión» y no podrá asignarse a otra salida.</p>
                    <form action="<?= url('comisiones/' . $c['id'] . '/iniciar') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-accent" data-confirm="¿Confirma iniciar esta comisión?">Iniciar comisión</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array($c['estado'], ['borrador', 'en_curso'], true) && can('comisiones.delete')): ?>
            <div class="card" style="border:1px solid var(--border-color)">
                <div class="card-header"><h3 style="margin:0;font-size:.95rem">Cancelar comisión</h3></div>
                <div class="card-body">
                    <form action="<?= url('comisiones/' . $c['id'] . '/cancelar') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label class="form-label" for="motivo_cancel">Motivo de cancelación</label>
                            <textarea id="motivo_cancel" name="motivo" class="form-textarea" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" data-confirm="¿Confirma cancelar esta comisión?">Cancelar comisión</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
