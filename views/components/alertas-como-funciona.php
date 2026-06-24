<?php
$configKm = $configKm ?? [];
$configDocs = $configDocs ?? [];
$aceite = alerta_config_por_tipo($configKm, 'cambio_aceite') ?? ($configKm[0] ?? null);
$seguro = alerta_config_por_tipo($configDocs, 'seguro') ?? ($configDocs[0] ?? null);
?>
<div class="alerta-como-funciona">
    <h2 class="alerta-como-funciona-titulo">¿Cómo funciona?</h2>

    <div class="alerta-como-funciona-grid">
        <section class="alerta-como-funciona-bloque">
            <h3>Mantenimiento (kilómetros)</h3>
            <ol class="alerta-como-funciona-pasos">
                <li>Registre el servicio en <strong>Mantenimiento</strong> y elija uno o más <strong>servicios realizados</strong> (aceite, afinación, llantas…).</li>
                <li>¿Necesita otro tipo de servicio? En <strong>Ajustes de alertas</strong> use «Agregar servicio de mantenimiento».</li>
                <li>Al <strong>finalizar</strong> el mantenimiento, el sistema reinicia el contador de km para cada servicio seleccionado.</li>
                <li>Las alertas solo aparecen <strong>después</strong> del primer mantenimiento finalizado de cada tipo; luego cuentan km y días desde ese registro.</li>
            </ol>

            <?php if ($aceite !== null): ?>
            <?php $u = alerta_config_umbrales_km($aceite); ?>
            <div class="alerta-como-funciona-ejemplo alerta-como-funciona-ejemplo--km">
                <strong>Ejemplo — <?= e($aceite['nombre'] ?? 'Cambio de aceite') ?></strong>
                <p>Si el último cambio de aceite fue a los 10,000 km y el vehículo hoy marca:</p>
                <ul>
                    <li><span class="badge badge-success">Aviso</span> a los <strong><?= alerta_config_fmt_num($u['aviso']) ?> km</strong> → alerta cuando llegue a <?= alerta_config_fmt_num(10000 + $u['aviso']) ?> km</li>
                    <li><span class="badge badge-warning">Atención</span> a los <strong><?= alerta_config_fmt_num($u['atencion']) ?> km</strong> → alerta cuando llegue a <?= alerta_config_fmt_num(10000 + $u['atencion']) ?> km</li>
                    <li><span class="badge badge-danger">Urgente</span> a los <strong><?= alerta_config_fmt_num($u['urgente']) ?> km</strong> → alerta cuando llegue a <?= alerta_config_fmt_num(10000 + $u['urgente']) ?> km</li>
                </ul>
                <p class="alerta-como-funciona-nota">
                    Los números de la tabla son <strong>km recorridos desde el último servicio</strong>, no el kilometraje total del tablero.
                    En este ejemplo el servicio anterior quedó en 10,000 km; usted puede cambiar los valores abajo.
                </p>
            </div>
            <?php endif; ?>
        </section>

        <section class="alerta-como-funciona-bloque">
            <h3>Documentos (días)</h3>
            <ol class="alerta-como-funciona-pasos">
                <li>Suba el documento con su <strong>fecha de vencimiento</strong> en el módulo Documentos.</li>
                <li>El sistema cuenta cuántos <strong>días faltan</strong> para que venza.</li>
                <li>Le avisa con la anticipación que ponga en la tabla.</li>
            </ol>

            <?php if ($seguro !== null): ?>
            <?php $d = alerta_config_umbrales_dias_doc($seguro); ?>
            <div class="alerta-como-funciona-ejemplo alerta-como-funciona-ejemplo--doc">
                <strong>Ejemplo — <?= e($seguro['nombre'] ?? 'Seguro') ?></strong>
                <ul>
                    <li><span class="badge badge-success">Aviso</span> <strong><?= alerta_config_fmt_num($d['aviso']) ?> días</strong> antes de vencer</li>
                    <li><span class="badge badge-warning">Atención</span> <strong><?= alerta_config_fmt_num($d['atencion']) ?> días</strong> antes</li>
                    <li><span class="badge badge-danger">Urgente</span> <strong><?= alerta_config_fmt_num($d['urgente']) ?> días</strong> antes (0 = el día que vence)</li>
                </ul>
            </div>
            <?php endif; ?>
        </section>
    </div>
</div>
