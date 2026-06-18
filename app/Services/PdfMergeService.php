<?php

declare(strict_types=1);

namespace App\Services;

use setasign\Fpdi\Fpdi;

final class PdfMergeService
{
    /**
     * @param list<string> $files Rutas absolutas a archivos PDF
     */
    public function mergeToFile(array $files, string $outputPath): void
    {
        if ($files === []) {
            throw new \InvalidArgumentException('No hay archivos PDF para combinar.');
        }

        $pdf = $this->buildMergedPdf($files);
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdf->Output('F', $outputPath);
    }

    /**
     * @param list<string> $files Rutas absolutas a archivos PDF
     */
    public function stream(array $files, string $downloadName): never
    {
        $pdf = $this->buildMergedPdf($files);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $downloadName . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        $pdf->Output('I', $downloadName);
        exit;
    }

    /**
     * @param list<string> $files
     */
    private function buildMergedPdf(array $files): Fpdi
    {
        $pdf = new Fpdi();

        foreach ($files as $file) {
            if (!is_file($file)) {
                throw new \InvalidArgumentException('Archivo no encontrado: ' . $file);
            }
            $pageCount = $pdf->setSourceFile($file);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        return $pdf;
    }
}
