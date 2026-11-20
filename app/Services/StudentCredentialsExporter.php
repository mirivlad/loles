<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class StudentCredentialsExporter
{
    public const HEADERS = ['ФИО', 'Логин', 'Пароль', 'Предприятие', 'Группы'];

    public function rows(Collection $students): array
    {
        return $students->map(fn ($student) => [
            (string) $student->name,
            (string) $student->username,
            (string) ($student->issuedCredential?->secret ?? '(не сохранён)'),
            (string) ($student->organization?->name ?? ''),
            (string) $student->groups->pluck('name')->implode(', '),
        ])->all();
    }

    public function writeXlsx(Collection $students, string $path): void
    {
        $book = new Spreadsheet();
        $book->getProperties()
            ->setCreator('LoLeS')
            ->setTitle('Ведомость выдачи логинов и паролей');
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Учётные данные');

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'Ведомость выдачи учётных данных');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '163A64']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(34);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Конфиденциально. Только для выдачи учащимся и доверенному куратору.');
        $sheet->getStyle('A2')->getFont()->getColor()->setRGB('9A3412');
        $sheet->getRowDimension(2)->setRowHeight(25);

        foreach (self::HEADERS as $column => $heading) {
            $sheet->setCellValueExplicit(chr(65 + $column) . '4', $heading, DataType::TYPE_STRING);
        }
        $sheet->getStyle('A4:E4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '245A83']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(28);

        $rowNumber = 5;
        foreach ($this->rows($students) as $row) {
            foreach ($row as $column => $value) {
                // Explicit text prevents formula injection and preserves leading zeros.
                $sheet->setCellValueExplicit(chr(65 + $column) . $rowNumber, $value, DataType::TYPE_STRING);
            }
            if ($rowNumber % 2 === 0) {
                $sheet->getStyle("A{$rowNumber}:E{$rowNumber}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            }
            $sheet->getRowDimension($rowNumber)->setRowHeight(23);
            $rowNumber++;
        }

        foreach (['A' => 35, 'B' => 20, 'C' => 19, 'D' => 33, 'E' => 33] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $lastRow = max(4, $rowNumber - 1);
        $sheet->getStyle("A4:E{$lastRow}")->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CBD5E1']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane('A5');
        $sheet->setAutoFilter("A4:E{$lastRow}");
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
    }

    public function writeDocx(Collection $students, string $path): void
    {
        $word = new PhpWord();
        $word->getDocInfo()->setCreator('LoLeS');
        $word->getDocInfo()->setTitle('Ведомость выдачи учётных данных');
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(10);

        $section = $word->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 16838, 'pageSizeH' => 11906,
            'marginTop' => 800, 'marginBottom' => 800,
            'marginLeft' => 750, 'marginRight' => 750,
        ]);
        $section->addText('Ведомость выдачи учётных данных', [
            'bold' => true, 'size' => 16, 'color' => '163A64',
        ], ['spaceAfter' => 140]);
        $section->addText('Конфиденциально. Передавайте только доверенному куратору.', [
            'size' => 9, 'italic' => true, 'color' => '9A3412',
        ], ['spaceAfter' => 200]);

        $word->addTableStyle('Credentials', [
            'borderSize' => 4, 'borderColor' => 'CBD5E1',
            'cellMargin' => 95,
        ]);
        $table = $section->addTable('Credentials');
        $widths = [3550, 1950, 1750, 3700, 3700];

        $table->addRow(540, ['tblHeader' => true, 'cantSplit' => true]);
        foreach (self::HEADERS as $index => $heading) {
            $cell = $table->addCell($widths[$index], [
                'bgColor' => '245A83', 'valign' => 'center',
            ]);
            $cell->addText($heading, ['bold' => true, 'color' => 'FFFFFF', 'size' => 10]);
        }

        foreach ($this->rows($students) as $index => $row) {
            $table->addRow(420, ['cantSplit' => true]);
            foreach ($row as $column => $value) {
                $cellStyle = ['valign' => 'center'];
                if ($index % 2 === 1) {
                    $cellStyle['bgColor'] = 'F1F5F9';
                }
                $table->addCell($widths[$column], $cellStyle)->addText($value, ['size' => 9]);
            }
        }

        $footer = $section->addFooter();
        $footer->addPreserveText('LoLeS · Ведомость для выдачи учащимся · Страница {PAGE}', [
            'size' => 8, 'color' => '64748B',
        ], ['alignment' => 'right']);

        IOFactory::createWriter($word, 'Word2007')->save($path);
    }
}
