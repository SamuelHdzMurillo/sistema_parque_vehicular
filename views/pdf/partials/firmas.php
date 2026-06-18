<table class="firmas-table">
    <tr>
        <?php foreach ($firmas as $firma): ?>
        <?php
            $img = pdf_firma_data_uri($firma['firma'] ?? null);
            $nombre = trim((string) ($firma['nombre'] ?? ''));
        ?>
        <td>
            <div class="firma-label"><?= e($firma['label']) ?></div>
            <div class="firma-espacio">
                <?php if ($img !== ''): ?>
                <img src="<?= $img ?>" alt="" class="firma-img">
                <?php endif; ?>
            </div>
            <div class="firma-linea"></div>
            <?php if ($nombre !== ''): ?>
            <div class="firma-nombre"><?= e($nombre) ?></div>
            <?php else: ?>
            <div class="firma-nombre">Nombre y firma</div>
            <?php endif; ?>
        </td>
        <?php endforeach; ?>
    </tr>
</table>
