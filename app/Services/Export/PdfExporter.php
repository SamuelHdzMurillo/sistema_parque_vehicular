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
        $options->set('isRemoteEnabled', false);
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
        $institution = htmlspecialchars((string) config('app', 'institution'), ENT_QUOTES, 'UTF-8');
        $generated = htmlspecialchars(date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

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
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
            h1 { font-size: 16px; margin-bottom: 4px; }
            .meta { font-size: 9px; color: #64748b; margin-bottom: 16px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #e2e8f0; text-align: left; padding: 6px; border: 1px solid #cbd5e1; }
            td { padding: 5px; border: 1px solid #e2e8f0; }
            tr:nth-child(even) td { background: #f8fafc; }
        </style></head>
        <body>
            <h1>{$titleEsc}</h1>
            <div class="meta">{$institution} · Generado: {$generated}</div>
            <table><thead><tr>{$thead}</tr></thead><tbody>{$tbody}</tbody></table>
        </body></html>
        HTML;
    }
}
