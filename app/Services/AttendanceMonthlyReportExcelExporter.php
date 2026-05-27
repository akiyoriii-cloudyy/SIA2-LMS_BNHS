<?php

namespace App\Services;

use App\Models\AttendanceMonthlyReport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AttendanceMonthlyReportExcelExporter
{
    /**
     * Build a print-ready XLSX workbook matching the web print view.
     */
    public function makeSpreadsheet(AttendanceMonthlyReport $report): Spreadsheet
    {
        $report->loadMissing(['lines', 'section', 'schoolYear', 'teacher.user']);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($report->monthName(), 0, 10).'_'.$report->calendarYear());
        $sheet->getDefaultRowDimension()->setRowHeight(18);

        $section = $report->section;
        $monthName = $report->monthName();
        $calendarYear = $report->calendarYear();
        $periodRange = $report->periodRangeLabel();
        $sectionLabel = ($section?->name ?? '—').($section?->grade_level ? ' (Grade '.$section->grade_level.')' : '');
        $schoolYearName = (string) ($report->schoolYear?->name ?? '—');
        $totalAbsent = (int) $report->lines->sum('absent_days');
        $generatedAt = $report->generated_at
            ?->timezone(AttendanceMonthlyReport::appTimezone())
            ->format('M j, Y g:i A') ?? '—';

        // Title (matches print header)
        $sheet->setCellValue('A1', 'Monthly Attendance Report');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Section:');
        $sheet->setCellValue('B2', $sectionLabel);
        $sheet->mergeCells('B2:E2');
        $sheet->setCellValue('F2', 'School Year:');
        $sheet->setCellValue('G2', $schoolYearName);
        $sheet->mergeCells('G2:I2');

        $sheet->setCellValue('A3', 'Month:');
        $sheet->setCellValue('B3', $monthName);
        $sheet->setCellValue('C3', 'Year:');
        $sheet->setCellValue('D3', $calendarYear);
        $sheet->setCellValue('E3', 'Coverage:');
        $sheet->setCellValue('F3', $periodRange);
        $sheet->mergeCells('F3:I3');

        $sheet->setCellValue('A4', 'School Days (section):');
        $sheet->setCellValue('B4', (int) $report->school_days_total);
        $sheet->setCellValue('C4', 'Total Absences:');
        $sheet->setCellValue('D4', $totalAbsent);
        $sheet->setCellValue('F4', 'Report ID:');
        $sheet->setCellValue('G4', '#'.$report->id);
        $sheet->setCellValue('H4', 'Generated:');
        $sheet->setCellValue('I4', $generatedAt);

        if ($report->emailed_at) {
            $sheet->setCellValue('A5', 'Emailed:');
            $sheet->setCellValue('B5', $report->emailed_at->timezone(AttendanceMonthlyReport::appTimezone())->format('M j, Y g:i A'));
            $sheet->mergeCells('B5:E5');
        }

        $notesRow = $report->notes ? 6 : 5;
        if ($report->notes) {
            $sheet->setCellValue('A6', 'Notes:');
            $sheet->setCellValue('B6', (string) $report->notes);
            $sheet->mergeCells('B6:I6');
            $sheet->getStyle('B6')->getAlignment()->setWrapText(true);
        }

        $sheet->getStyle('A2:A'.$notesRow)->getFont()->setBold(true);
        $sheet->getStyle('C3:C4')->getFont()->setBold(true);
        $sheet->getStyle('E3:F4')->getFont()->setBold(true);

        $headerRow = $notesRow + 2;
        $headers = ['#', 'Student', 'LRN', 'School Days', 'Present', 'Late', 'Excused', 'Absent', 'Remarks'];

        foreach ($headers as $index => $headerText) {
            $columnLetter = chr(ord('A') + $index);
            $sheet->setCellValue($columnLetter.$headerRow, $headerText);
        }

        $headerRange = 'A'.$headerRow.':I'.$headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B1F44'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(32);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(10);
        $sheet->getColumnDimension('I')->setWidth(28);

        $startRow = $headerRow + 1;
        $lineCount = $report->lines->count();

        foreach ($report->lines as $index => $line) {
            $row = $startRow + $index;
            $absentDays = (int) $line->absent_days;

            $sheet->setCellValue('A'.$row, $index + 1);
            $sheet->setCellValue('B'.$row, (string) ($line->student_name ?? ''));
            $sheet->setCellValue('C'.$row, $line->lrn ?? '—');
            $sheet->setCellValue('D'.$row, (int) $line->school_days);
            $sheet->setCellValue('E'.$row, (int) $line->present_days);
            $sheet->setCellValue('F'.$row, (int) $line->late_days);
            $sheet->setCellValue('G'.$row, (int) $line->excused_days);
            $sheet->setCellValue('H'.$row, $absentDays);
            $sheet->setCellValue('I'.$row, (string) ($line->remarks ?? '—'));

            $sheet->getStyle('A'.$row.':H'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I'.$row)->getAlignment()->setWrapText(true);

            $sheet->getStyle('A'.$row.':I'.$row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E5E7EB'],
                    ],
                ],
            ]);

            if ($absentDays > 0) {
                $sheet->getStyle('H'.$row)->getFont()->getColor()->setRGB('B91C1C');
                $sheet->getStyle('H'.$row)->getFont()->setBold(true);
            }
        }

        $footerRow = $startRow + max($lineCount, 1);
        $sheet->setCellValue('A'.$footerRow, 'Printed from BNHS LMS — same data as web print view & mobile sync. Report #'.$report->id.'.');
        $sheet->mergeCells('A'.$footerRow.':I'.$footerRow);
        $sheet->getStyle('A'.$footerRow)->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A'.$footerRow)->getFont()->getColor()->setRGB('6B7280');

        // Print settings (open in Excel → File → Print)
        $lastDataRow = $lineCount > 0 ? ($startRow + $lineCount - 1) : $headerRow;
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setPrintArea('A1:I'.$lastDataRow);
        $sheet->getPageMargins()->setTop(0.5)->setRight(0.3)->setLeft(0.3)->setBottom(0.5);
        $sheet->getHeaderFooter()->setOddHeader('&C&"Arial,Bold"Monthly Attendance Report — '.$monthName.' '.$calendarYear);
        $sheet->getHeaderFooter()->setOddFooter('&LReport #'.$report->id.'&RPage &P of &N');
        $sheet->freezePane('A'.$startRow);

        return $spreadsheet;
    }
}
