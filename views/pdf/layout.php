<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php
    require_once view_path('pdf/helpers.php');
    $logoUri = pdf_logo_data_uri();
    $logoSize = brand_logo_pdf_size();
    ?>
    <style>
        @page { margin: 10mm 10mm 12mm 10mm; }
        @page factura { margin: 5mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #000; line-height: 1.3; }
        .pdf-header {
            border-bottom: 3px solid #000;
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
        .pdf-title { font-size: 14px; font-weight: bold; text-align: right; margin: 0; color: #000; }
        .pdf-subtitle { font-size: 9.5px; font-weight: bold; text-align: right; color: #000; margin-top: 2px; }
        .section { margin-top: 8px; margin-bottom: 5px; }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000;
            border-bottom: 2px solid #000;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }
        .block-caption {
            margin: 6px 0 2px;
            font-size: 8px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
        }
        .fields-row { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .field-cell { width: 50%; padding: 2px 6px 3px 0; vertical-align: top; }
        .field-label {
            display: block;
            font-size: 8px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            margin-bottom: 1px;
        }
        .field-value {
            display: block; min-height: 12px; border-bottom: 1.5px solid #000;
            font-size: 9.5px; padding-bottom: 2px;
        }
        .text-block { border: 1.5px solid #000; padding: 5px; min-height: 24px; margin-top: 3px; }
        .factura-cap {
            margin: 6px 0 3px;
            font-size: 8px;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
        }
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
        .factura-box { border: 2px dashed #000; width: 200mm; height: 287mm; margin: 0; }
        .factura-note { border: 2px solid #000; background: #fff; padding: 8px; font-size: 9px; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1.5px solid #000; padding: 4px 6px; font-size: 9px; }
        table.data th { background: #d9d9d9; text-align: left; font-weight: bold; color: #000; }
        .checklist td.center { text-align: center; width: 48px; font-weight: bold; font-size: 11px; }
        .firmas-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .firmas-table td { width: 33.33%; vertical-align: bottom; padding: 6px 10px 0; text-align: center; }
        .firma-label { font-size: 8px; font-weight: bold; color: #000; text-transform: uppercase; margin-bottom: 4px; }
        .firma-espacio { height: 44px; margin-bottom: 2px; }
        .firma-linea { border-top: 2px solid #000; height: 0; margin: 0; }
        .firma-img { max-height: 40px; max-width: 140px; display: block; margin: 0 auto; }
        .firma-nombre { font-size: 9px; font-weight: bold; color: #000; margin-top: 3px; }
        .footer-note {
            margin-top: 8px;
            font-size: 7.5px;
            font-weight: bold;
            color: #000;
            border-top: 2px solid #000;
            padding-top: 4px;
        }
        .luz-none { font-style: italic; color: #000; }
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
