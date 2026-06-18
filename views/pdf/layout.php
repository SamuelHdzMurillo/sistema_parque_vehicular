<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php
    require_once view_path('pdf/helpers.php');
    $greenDark = pdf_brand('green_dark', '#237F3A');
    $green = pdf_brand('green', '#76BC43');
    $orange = pdf_brand('orange', '#F17829');
    $logoUri = pdf_logo_data_uri();
    $logoSize = brand_logo_pdf_size();
    ?>
    <style>
        @page { margin: 10mm 10mm 12mm 10mm; }
        @page factura { margin: 5mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #212529; line-height: 1.3; }
        .pdf-header {
            border-bottom: 2px solid <?= $orange ?>;
            padding-bottom: 5px;
            margin-bottom: 9px;
        }
        .pdf-header-table { width: 100%; border-collapse: collapse; }
        .pdf-header-table td { vertical-align: middle; }
        .pdf-logo {
            height: <?= (int) $logoSize['height'] ?>px;
            width: <?= (int) $logoSize['width'] ?>px;
            display: block;
        }
        .pdf-title { font-size: 13px; font-weight: bold; text-align: right; margin: 0; color: <?= $greenDark ?>; }
        .pdf-subtitle { font-size: 9px; text-align: right; color: #6c757d; margin-top: 2px; }
        .section { margin-top: 8px; margin-bottom: 5px; }
        .section-title {
            font-size: 9.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: <?= $greenDark ?>;
            border-bottom: 1px solid <?= $green ?>;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }
        .fields-row { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .field-cell { width: 50%; padding: 2px 6px 3px 0; vertical-align: top; }
        .field-label { display: block; font-size: 7.5px; color: #6c757d; text-transform: uppercase; margin-bottom: 1px; }
        .field-value {
            display: block; min-height: 12px; border-bottom: 1px solid #ccc;
            font-size: 9.5px; padding-bottom: 2px;
        }
        .text-block { border: 1px solid #ccc; padding: 5px; min-height: 24px; margin-top: 3px; }
        .factura-cap { margin: 6px 0 3px; font-size: 7.5px; color: #64748b; text-transform: uppercase; }
        .factura-page {
            page: factura;
            page-break-before: always;
            margin: 0;
            padding: 0;
        }
        .factura-viewport {
            width: 200mm;
            height: 287mm;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        .factura-viewport img { display: block; }
        .factura-box { border: 1px dashed #999; width: 200mm; height: 287mm; margin: 0; }
        .factura-note { border: 1px solid #ccc; background: #f8fafc; padding: 8px; font-size: 9px; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #ccc; padding: 4px 6px; font-size: 9px; }
        table.data th { background: #f5f5f5; text-align: left; font-weight: bold; }
        .checklist td.center { text-align: center; width: 48px; }
        .firmas-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .firmas-table td { width: 33.33%; vertical-align: bottom; padding: 6px 10px 0; text-align: center; }
        .firma-label { font-size: 8px; color: #6c757d; text-transform: uppercase; margin-bottom: 4px; }
        .firma-espacio { height: 44px; margin-bottom: 2px; }
        .firma-linea { border-top: 1px solid #333; height: 0; margin: 0; }
        .firma-img { max-height: 40px; max-width: 140px; display: block; margin: 0 auto; }
        .firma-nombre { font-size: 9px; color: #212529; margin-top: 3px; }
        .footer-note {
            margin-top: 8px;
            font-size: 7.5px;
            color: #6c757d;
            border-top: 1px solid #ccc;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="pdf-header">
        <table class="pdf-header-table">
            <tr>
                <td style="width:45%">
                    <?php if ($logoUri !== ''): ?>
                    <img src="<?= $logoUri ?>" alt="CECYTE BCS" class="pdf-logo">
                    <?php else: ?>
                    <strong><?= e((string) config('app', 'institution')) ?></strong>
                    <?php endif; ?>
                </td>
                <td style="width:55%">
                    <div class="pdf-title"><?= e($pdfTitle ?? 'Formato') ?></div>
                    <?php if (!empty($pdfSubtitle)): ?>
                    <div class="pdf-subtitle"><?= e($pdfSubtitle) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <?= $pdfBody ?? '' ?>

    <?php if (empty($pdfSkipFooter)): ?>
    <div class="footer-note">
        Generado el <?= e(date('d/m/Y H:i')) ?> · <?= e((string) config('app', 'institution')) ?> · Uso institucional interno.
    </div>
    <?php endif; ?>
</body>
</html>
