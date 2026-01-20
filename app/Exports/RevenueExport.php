<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;

class RevenueExport implements
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
        protected string $source
    ) {}

    /**
     * @return Collection
     */
    public function collection()
    {
        // Handle different field names for totals
        $field = $this->source === 'index' ? 'total_amount' : 'amount';
        $this->totals['total_amount'] = $this->data->sum($field);
        return $this->data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        if ($this->source === 'show') {
            return [
                'Driver', 
                'Invoice No.', 
                'Date', 
                'Amount'
            ];
        }

        return [
            'Franchise',
            'Date',
            'Amount'
        ];
    }

    /**
     * Maps and transforms the data for export
     */
    public function map($row): array
    {
        if ($this->source === 'show') {
            return [
                $row->driver->user->username ?? 'N/A',
                $row->invoice_no,
                Carbon::parse($row->payment_date)->format('M j, Y h:i A'),
                $row->amount,
            ];
        }

        // Index Method Mapping
        $nameKey = $this->tabName . '_name';
        $name = $row->$nameKey ?? 'N/A';
        // Resolve Date Logic
        $dateDisplay = 'N/A';
        if (isset($row->month_name)) {
            // Monthly
            $dateDisplay = $row->month_name;
        } elseif (isset($row->week_start)) {
            // Weekly
            $start = Carbon::parse($row->week_start);
            $end = Carbon::parse($row->week_end);
            $dateDisplay = $start->format('M j') . ' - ' . $end->format('M j, Y');
        } elseif (isset($row->payment_date)) {
            // Daily (grouped by date)
            $dateDisplay = Carbon::parse($row->payment_date)->format('M j, Y');
        }
        return [
            $name,
            $dateDisplay,
            $row->total_amount ?? 0,
        ];
    }

    public function columnFormats(): array
    {
        // In 'show', amount is column D. In 'index', it's column C.
        $col = $this->source === 'show' ? 'D' : 'C';
        return [
            $col => '"₱"#,##0.00'
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastColIndex = $this->source === 'show' ? 4 : 3;
                $lastRow = $sheet->getHighestRow();
                $totalRow = $lastRow + 2;

                // Title and Headers logic...
                $sheet->insertNewRowBefore(1, 1);
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->setCellValue('A1', $this->title);
                
                // Grand Total Label
                $labelColLetter = Coordinate::stringFromColumnIndex($lastColIndex - 1);
                $sheet->mergeCells("A{$totalRow}:{$labelColLetter}{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", "GRAND TOTAL");
                
                // Grand Total Value
                $this->setTotalCell($sheet, $lastColIndex, $totalRow, $this->totals['total_amount']);
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