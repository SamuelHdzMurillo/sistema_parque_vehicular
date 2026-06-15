<?php

declare(strict_types=1);

namespace App\Factories;

use App\Services\Export\CsvExporter;
use App\Services\Export\ExporterInterface;
use App\Services\Export\PdfExporter;
use App\Services\Export\XlsxExporter;
use InvalidArgumentException;

final class ExporterFactory
{
    public static function make(string $format): ExporterInterface
    {
        return match (strtolower($format)) {
            'csv' => new CsvExporter(),
            'xlsx', 'excel' => new XlsxExporter(),
            'pdf' => new PdfExporter(),
            default => throw new InvalidArgumentException("Formato de exportación no soportado: {$format}"),
        };
    }

    public static function supportedFormats(): array
    {
        return ['csv', 'xlsx', 'pdf'];
    }
}
