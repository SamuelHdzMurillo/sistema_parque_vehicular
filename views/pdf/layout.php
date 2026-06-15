<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <?php
    require_once view_path('pdf/helpers.php');
    $greenDark = pdf_brand('green_dark', '#1A5E20');
    $green = pdf_brand('green', '#4CAF50');
    $greenLight = pdf_brand('green_light', '#e8f5e9');
    $greenMuted = pdf_brand('green_muted', '#c8e6c9');
    $logoUri = pdf_logo_data_uri();
    ?>
    <style>
        @page { margin: 14mm 12mm 18mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #212529; line-height: 1.35; }
        .header-banner {
            background: <?= $greenDark ?>;
            padding: 10px 12px;
            border-radius: 6px 6px 0 0;
        }
        .header-top { width: 100%; border-collapse: collapse; }
        .header-top td { vertical-align: middle; }
        .logo-wrap {
            background: #ffffff;
            border-radius: 5px;
            padding: 5px 10px;
            display: inline-block;
        }
        .logo-horizontal { max-height: 46px; max-width: 300px; display: block; }
        .header-fallback {
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .5px;
        }
        .header-fallback small {
            display: block;
            font-size: 9px;
            font-weight: normal;
            color: <?= $greenMuted ?>;
            text-transform: none;
            margin-top: 2px;
        }
        .doc-title { font-size: 13px; font-weight: bold; text-align: right; color: #ffffff; margin: 0; }
        .doc-subtitle { font-size: 9px; text-align: right; color: <?= $greenMuted ?>; margin-top: 3px; }
        .header-accent { height: 4px; background: <?= $green ?>; border-radius: 0 0 6px 6px; margin-bottom: 14px; }
        .section { margin-top: 12px; margin-bottom: 8px; }
        .section-title {
            background: <?= $greenLight ?>;
            padding: 5px 8px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: <?= $greenDark ?>;
            border-left: 4px solid <?= $green ?>;
        }
        .fields-row { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .field-cell { width: 50%; padding: 3px 6px 6px 0; vertical-align: top; }
        .field-label { display: block; font-size: 8px; color: <?= $greenDark ?>; text-transform: uppercase; margin-bottom: 2px; font-weight: bold; }
        .field-value {
            display: block; min-height: 14px; border-bottom: 1px solid <?= $green ?>;
            font-size: 10px; padding-bottom: 2px;
        }
        .text-block { border: 1px solid <?= $green ?>; background: #fafdfa; padding: 8px; min-height: 36px; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.data th, table.data td { border: 1px solid #b7dfb9; padding: 4px 6px; font-size: 9px; }
        table.data th { background: <?= $greenDark ?>; color: #ffffff; text-align: left; }
        table.data tr:nth-child(even) td { background: <?= $greenLight ?>; }
        .checklist td.center { text-align: center; width: 48px; }
        .firmas-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .firmas-table td { width: 33.33%; vertical-align: bottom; padding: 8px 10px 0; text-align: center; }
        .firma-linea { border-top: 2px solid <?= $greenDark ?>; height: 42px; margin-bottom: 4px; }
        .firma-img { max-height: 38px; max-width: 140px; display: block; margin: 0 auto 2px; }
        .firma-label { font-size: 8px; color: <?= $greenDark ?>; font-weight: bold; text-transform: uppercase; }
        .firma-nombre { font-size: 9px; color: #6c757d; margin-top: 2px; }
        .footer-note {
            margin-top: 14px;
            font-size: 8px;
            color: <?= $greenDark ?>;
            border-top: 2px solid <?= $green ?>;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="header-banner">
        <table class="header-top">
            <tr>
                <td style="width:58%">
                    <?php if ($logoUri !== ''): ?>
                    <div class="logo-wrap">
                        <img src="<?= $logoUri ?>" alt="CECYTE BCS" class="logo-horizontal">
                    </div>
                    <?php else: ?>
                    <div class="header-fallback">
                        <?= e((string) config('app', 'institution')) ?>
                        <small><?= e((string) config('app', 'name')) ?></small>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="width:42%">
                    <div class="doc-title"><?= e($pdfTitle ?? 'Formato') ?></div>
                    <?php if (!empty($pdfSubtitle)): ?>
                    <div class="doc-subtitle"><?= e($pdfSubtitle) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
    <div class="header-accent"></div>

    <?= $pdfBody ?? '' ?>

    <div class="footer-note">
        Documento generado el <?= e(date('d/m/Y H:i')) ?> ·
        <?= e((string) config('app', 'institution')) ?> ·
        Uso exclusivo institucional · Las firmas autógrafas tienen validez operativa interna.
    </div>
</body>
</html>
