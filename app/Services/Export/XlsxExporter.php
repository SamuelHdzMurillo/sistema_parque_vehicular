<?php

declare(strict_types=1);

namespace App\Services\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

final class XlsxExporter implements ExporterInterface
{
    public function export(string $title, array $headers, array $rows, string $filename): string
    {
        $path = storage_path('exports/' . $filename . '.xlsx');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));

        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . $this->columnLetter(count($headers)) . '1');

        $col = 1;
        foreach ($headers as $header) {
            $cell = $this->columnLetter($col) . '3';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE2E8F0');
            $col++;
        }

        $rowNum = 4;
        foreach ($rows as $row) {
            $col = 1;
            foreach ($headers as $header) {
                $sheet->setCellValue($this->columnLetter($col) . $rowNum, $row[$header] ?? '');
                $col++;
            }
            $rowNum++;
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->columnLetter($i))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    public function contentType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function fileExtension(): string
    {
        return 'xlsx';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }
        return $letter;
    }
}
