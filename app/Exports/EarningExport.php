<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

class EarningExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    WithEvents,
    ShouldAutoSize
{
    use Exportable;

    // We will store the calculated totals here
    protected array $totals = [];

    public function __construct(
        protected Collection $data,
        protected string $title,
        protected string $tabName,
        protected $feeTypes
    ) {}

    public function collection()
    {
        // 1. Calculate Grand Totals for ALL numeric columns before export
        $this->totals['total_amount'] = $this->data->sum('total_amount');
        $this->totals['driver_earning'] = $this->data->sum('driver_earning');

        foreach ($this->feeTypes as $type) {
            $key = 'total_' . $type['slug'];
            $this->totals[$key] = $this->data->sum($key);
        }

        return $this->data;
    }

    /**
     * Fixes the Date Logic and Maps the columns
     */
    public function map($row): array
    {
        // A. Detail View Mapping (Show Method)
        if ($this->tabName === 'show') {
            $columns = [
                $row->invoice_no, // Column A
                Carbon::parse($row->payment_date)->format('M d, Y h:i A'),
                $row->total_amount ?? 0,
            ];
        } 
        // B. Aggregate View Mapping (Index Method)
        else {
            // 1. Resolve Name
            $nameKey = $this->tabName . '_name';
            $name = $row->$nameKey ?? 'N/A';

            // 2. Resolve Date Logic (Fixes empty/raw dates)
            $dateDisplay = 'N/A';
            if (isset($row->month_name)) {
                // Monthly
                $dateDisplay = $row->month_name;
            } elseif (isset($row->week_start)) {
                // Weekly
                $start = Carbon::parse($row->week_start);
                $end = Carbon::parse($row->week_end);
                $dateDisplay = $start->format('M j') . ' - ' . $end->format('M j, Y');
            } else {
                // Daily (formatted)
                $dateDisplay = Carbon::parse($row->payment_date)->format('M j, Y');
            }

            // 3. Build Row
            $columns = [
                $name,
                $row->driver_username ?? 'N/A',
                $dateDisplay,
                $row->total_amount ?? 0,
            ];
        }

        // 4. Dynamic Fees
        foreach ($this->feeTypes as $type) {
            $key = 'total_' . $type['slug'];
            $columns[] = $row->$key ?? 0;
        }

        // 5. Driver Earning
        $columns[] = $row->driver_earning ?? 0;

        return $columns;
    }

    public function headings(): array
    {
        if ($this->tabName === 'show') {
            $headers = ['Invoice No.', 'Date', 'Trip Amount'];
        } else {
            $headers = [ucfirst($this->tabName), 'Driver', 'Date', 'Total Amount'];
        }

        foreach ($this->feeTypes as $type) {
            $headers[] = $type['display'];
        }
        $headers[] = 'Driver Earning';
        return $headers;
    }

    public function columnFormats(): array
    {
        // if show format (C) to the end as Currency else (D) 
        if ($this->tabName === 'show') {
            return [
                'C:Z' => '"₱"#,##0.00',
            ];
        } else {
            return [
                'D:Z' => '"₱"#,##0.00',
            ];
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastColumn = $sheet->getHighestColumn();
                $lastRow = $sheet->getHighestRow();
                
                // --- 1. Header Styling ---
                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A1', $this->title);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A2:{$lastColumn}2")->getFont()->setBold(true);

                // --- 2. Grand Total Row ---
                $totalRowIndex = $lastRow + 2; 

                // Label "GRAND TOTAL" (Merge A-B) if "show" else (Merge A-C)
                if ($this->tabName === 'show') {
                    $sheet->mergeCells("A{$totalRowIndex}:B{$totalRowIndex}");
                } else {
                    $sheet->mergeCells("A{$totalRowIndex}:C{$totalRowIndex}");
                }
                $sheet->setCellValue("A{$totalRowIndex}", "GRAND TOTAL");
                $sheet->getStyle("A{$totalRowIndex}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalRowIndex}")->getAlignment()->setHorizontal('right');

                // --- 3. Fill Numeric Totals ---
                // Start at (C) if show else (D)
                if ($this->tabName === 'show') {
                    $colIndex = 3;
                } else {
                    $colIndex = 4;
                }

                // A. Total Amount
                $this->setTotalCell($sheet, $colIndex, $totalRowIndex, $this->totals['total_amount']);
                $colIndex++;

                // B. Dynamic Fees
                foreach ($this->feeTypes as $type) {
                    $key = 'total_' . $type['slug'];
                    $this->setTotalCell($sheet, $colIndex, $totalRowIndex, $this->totals[$key]);
                    $colIndex++;
                }

                // C. Driver Earning
                $this->setTotalCell($sheet, $colIndex, $totalRowIndex, $this->totals['driver_earning']);
            },
        ];
    }

    /**
     * Helper to set value, bold it, and format currency
     */
    private function setTotalCell($sheet, $colIndex, $rowIndex, $value)
    {
        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
        $sheet->setCellValue("{$colLetter}{$rowIndex}", $value);
        $sheet->getStyle("{$colLetter}{$rowIndex}")->getFont()->setBold(true);
        $sheet->getStyle("{$colLetter}{$rowIndex}")->getNumberFormat()->setFormatCode('"₱"#,##0.00');
    }
}