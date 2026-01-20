<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        margin: 10px;
    }

    h1 {
        text-align: center;
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 5px;
        border-bottom: 2px solid #000;
        padding-bottom: 5px;
    }

    .payroll-header-box {
        width: 100%;
        border: 1px solid #777;
        padding: 10px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .payroll-header-row {
        width: 100%;
        display: table;
        table-layout: fixed;
        margin-bottom: 5px;
    }

    /* Adjusted columns to span evenly across the space */
    .payroll-header-col {
        display: table-cell;
        width: 33.33%; /* Now 3 columns */
        font-size: 11px;
    }
    /* Set the fourth column (period) to take less space to center the totals */
    .payroll-header-col.period {
        width: 25%;
    }
    .payroll-header-col.total {
        width: 37.5%;
    }

    .payroll-header-label {
        font-weight: bold;
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        border: 1px solid #777;
        padding: 6px;
    }

    th {
        background: #f0f0f0;
        font-weight: bold;
        text-align: center;
        vertical-align: top;
        text-transform: uppercase;
        font-size: 11px;
    }

    td.numeric {
        text-align: right;
    }

    .grand-total {
        font-weight: bold;
        background: #e8e8e8;
    }
    </style>

</head>
<body>

    <h1>{{ $title }}</h1>

    {{-- Formal Payroll Header Section --}}
    <div class="payroll-header-box">
        <div class="payroll-header-row">
            <div class="payroll-header-col">
                <span class="payroll-header-label">DRIVER USERNAME:</span> {{ $payrollData['driver_name'] }}
            </div>
            <div class="payroll-header-col">
                <span class="payroll-header-label">DRIVER ID:</span> {{ $payrollData['driver_id_display'] }}
            </div>
            <div class="payroll-header-col">
                <span class="payroll-header-label">{{ $payrollData['period'] }}:</span> {{ $payrollData['period_label'] }}
            </div>
        </div>

        <div class="payroll-header-row">
            {{-- Empty column for alignment/spacing --}}
            <div class="payroll-header-col"></div>

            {{-- Display Grand Totals in the header for easy summary viewing --}}
            <div class="payroll-header-col" style="font-weight: bold; color: #16a34a;">
                <span class="payroll-header-label">TOTAL EARNING:</span> ₱{{ number_format($payrollData['grand_totals']['driver_earning'], 2) }}
            </div>
            <div class="payroll-header-col" style="font-weight: bold; color: #dc2626;">
                <span class="payroll-header-label">TOTAL DEDUCTION:</span> ₱{{ number_format($payrollData['grand_totals']['total_deduction'], 2) }}
            </div>
        </div>
    </div>
    {{-- End Formal Payroll Header Section --}}

    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach($rows as $row)
                <tr class="{{ $row['invoice_no'] === 'GRAND TOTAL' ? 'grand-total' : '' }}">

                    @foreach($dataKeys as $key)
                        @php
                            $value = $row[$key] ?? '';
                            $isCurrency = in_array($key, ['total_trip_amount', 'total_deduction', 'driver_earning']);
                        @endphp

                        @if($isCurrency)
                            <td class="numeric">
                                @if($value !== '' && $value !== null)
                                    ₱{{ number_format((float)$value, 2) }}
                                @endif
                            </td>
                        @else
                            <td>
                                {{ $value }}
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
