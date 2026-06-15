<?php

declare(strict_types=1);

namespace App\Services\Export;

final class CsvExporter implements ExporterInterface
{
    public function export(string $title, array $headers, array $rows, string $filename): string
    {
        $path = storage_path('exports/' . $filename . '.csv');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo crear el archivo CSV');
        }

        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $row[$header] ?? '';
            }
            fputcsv($handle, $line);
        }

        fclose($handle);

        return $path;
    }

    public function contentType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    public function fileExtension(): string
    {
        return 'csv';
    }
}
