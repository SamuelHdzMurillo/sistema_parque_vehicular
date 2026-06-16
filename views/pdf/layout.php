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
        @page { margin: 14mm 12mm 16mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #212529; line-height: 1.4; }
        .pdf-header {
            border-bottom: 2px solid <?= $orange ?>;
            padding-bottom: 8px;
            margin-bottom: 14px;
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
        .section { margin-top: 12px; margin-bottom: 8px; }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: <?= $greenDark ?>;
            border-bottom: 1px solid <?= $green ?>;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .fields-row { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .field-cell { width: 50%; padding: 3px 6px 6px 0; vertical-align: top; }
        .field-label { display: block; font-size: 8px; color: #6c757d; text-transform: uppercase; margin-bottom: 2px; }
        .field-value {
            display: block; min-height: 14px; border-bottom: 1px solid #ccc;
            font-size: 10px; padding-bottom: 2px;
        }
        .text-block { border: 1px solid #ccc; padding: 8px; min-height: 36px; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #ccc; padding: 4px 6px; font-size: 9px; }
        table.data th { background: #f5f5f5; text-align: left; font-weight: bold; }
        .checklist td.center { text-align: center; width: 48px; }
        .firmas-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .firmas-table td { width: 33.33%; vertical-align: bottom; padding: 8px 10px 0; text-align: center; }
        .firma-linea { border-top: 1px solid #333; height: 42px; margin-bottom: 4px; }
        .firma-img { max-height: 38px; max-width: 140px; display: block; margin: 0 auto 2px; }
        .firma-label { font-size: 8px; color: #6c757d; text-transform: uppercase; }
        .firma-nombre { font-size: 9px; color: #6c757d; margin-top: 2px; }
        .footer-note {
            margin-top: 14px;
            font-size: 8px;
            color: #6c757d;
            border-top: 1px solid #ccc;
            padding-top: 6px;
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

    <div class="footer-note">
        Generado el <?= e(date('d/m/Y H:i')) ?> · <?= e((string) config('app', 'institution')) ?> · Uso institucional interno.
    </div>
</body>
</html>
