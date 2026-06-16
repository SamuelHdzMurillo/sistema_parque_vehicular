<?php

declare(strict_types=1);

namespace App\Services\Export;

use Dompdf\Dompdf;
use Dompdf\Options;

final class PdfExporter implements ExporterInterface
{
    public function export(string $title, array $headers, array $rows, string $filename): string
    {
        $path = storage_path('exports/' . $filename . '.pdf');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $html = $this->buildHtml($title, $headers, $rows);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        file_put_contents($path, $dompdf->output());

        return $path;
    }

    public function contentType(): string
    {
        return 'application/pdf';
    }

    public function fileExtension(): string
    {
        return 'pdf';
    }

    /**
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     */
    private function buildHtml(string $title, array $headers, array $rows): string
    {
        require_once view_path('pdf/helpers.php');

        $institution = htmlspecialchars((string) config('app', 'institution'), ENT_QUOTES, 'UTF-8');
        $generated = htmlspecialchars(date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $greenDark = pdf_brand('green_dark', '#237F3A');
        $green = pdf_brand('green', '#76BC43');
        $orange = pdf_brand('orange', '#F17829');
        $logoUri = pdf_logo_data_uri();
        $logoSize = brand_logo_pdf_size();
        $logoHtml = $logoUri !== ''
            ? '<img src="' . $logoUri . '" alt="CECYTE" style="height:' . (int) $logoSize['height'] . 'px;width:' . (int) $logoSize['width'] . 'px">'
            : '<strong>' . $institution . '</strong>';

        $thead = '';
        foreach ($headers as $header) {
            $thead .= '<th>' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $tbody = '';
        foreach ($rows as $row) {
            $tbody .= '<tr>';
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                $tbody .= '<td>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $tbody .= '</tr>';
        }

        return <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head><meta charset="UTF-8"><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #212529; }
            .pdf-header { border-bottom: 2px solid {$orange}; padding-bottom: 8px; margin-bottom: 12px; }
            .header-table { width: 100%; border-collapse: collapse; }
            .header-table td { vertical-align: middle; }
            .report-title { font-size: 13px; font-weight: bold; color: {$greenDark}; text-align: right; margin: 0; }
            .report-meta { font-size: 9px; color: #6c757d; text-align: right; margin-top: 2px; }
            table.data { width: 100%; border-collapse: collapse; }
            table.data th { background: #f5f5f5; text-align: left; padding: 6px; border: 1px solid #ccc; font-weight: bold; }
            table.data td { padding: 5px; border: 1px solid #ccc; }
            .footer { margin-top: 12px; font-size: 8px; color: #6c757d; border-top: 1px solid #ccc; padding-top: 6px; }
        </style></head>
        <body>
            <div class="pdf-header">
                <table class="header-table">
                    <tr>
                        <td style="width:45%">{$logoHtml}</td>
                        <td style="width:55%">
                            <div class="report-title">{$titleEsc}</div>
                            <div class="report-meta">{$institution} · {$generated}</div>
                        </td>
                    </tr>
                </table>
            </div>
            <table class="data"><thead><tr>{$thead}</tr></thead><tbody>{$tbody}</tbody></table>
            <div class="footer">{$institution} · Uso institucional interno</div>
        </body></html>
        HTML;
    }
}
