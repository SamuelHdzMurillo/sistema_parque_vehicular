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
        $greenDark = pdf_brand('green_dark', '#1A5E20');
        $green = pdf_brand('green', '#4CAF50');
        $greenLight = pdf_brand('green_light', '#e8f5e9');
        $greenMuted = pdf_brand('green_muted', '#c8e6c9');
        $logoUri = pdf_logo_data_uri();
        $logoHtml = $logoUri !== ''
            ? '<div style="background:#fff;border-radius:5px;padding:5px 10px;display:inline-block"><img src="' . $logoUri . '" alt="CECYTE" style="max-height:42px;max-width:280px"></div>'
            : '<div style="color:#fff;font-size:15px;font-weight:bold">' . $institution . '</div>';

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
            .header-banner { background: {$greenDark}; padding: 10px 12px; border-radius: 6px 6px 0 0; }
            .header-accent { height: 4px; background: {$green}; border-radius: 0 0 6px 6px; margin-bottom: 14px; }
            .header-table { width: 100%; border-collapse: collapse; }
            .header-table td { vertical-align: middle; }
            .report-title { font-size: 14px; font-weight: bold; color: #fff; text-align: right; margin: 0; }
            .report-meta { font-size: 9px; color: {$greenMuted}; text-align: right; margin-top: 3px; }
            table.data { width: 100%; border-collapse: collapse; }
            table.data th { background: {$greenDark}; color: #fff; text-align: left; padding: 6px; border: 1px solid #b7dfb9; }
            table.data td { padding: 5px; border: 1px solid #b7dfb9; }
            table.data tr:nth-child(even) td { background: {$greenLight}; }
            .footer { margin-top: 12px; font-size: 8px; color: {$greenDark}; border-top: 2px solid {$green}; padding-top: 6px; }
        </style></head>
        <body>
            <div class="header-banner">
                <table class="header-table">
                    <tr>
                        <td style="width:58%">{$logoHtml}</td>
                        <td style="width:42%">
                            <div class="report-title">{$titleEsc}</div>
                            <div class="report-meta">{$institution} · Generado: {$generated}</div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="header-accent"></div>
            <table class="data"><thead><tr>{$thead}</tr></thead><tbody>{$tbody}</tbody></table>
            <div class="footer">{$institution} · Uso exclusivo institucional</div>
        </body></html>
        HTML;
    }
}
