<?php

declare(strict_types=1);

namespace App\Services\Export;

interface ExporterInterface
{
    /**
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     */
    public function export(string $title, array $headers, array $rows, string $filename): string;

    public function contentType(): string;

    public function fileExtension(): string;
}
