<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px; /* Reduced font size to fit more columns */
        margin: 10px;
    }

    h1 {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 15px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        border: 1px solid #777;
        padding: 6px; /* Reduced padding */
    }

    th {
        background: #f0f0f0;
        font-weight: bold;
        text-align: center;
        vertical-align: top;
        text-transform: uppercase;
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

    <table>
        <thead>
            <tr>
                {{-- Dynamically render ALL headings (standard + breakdown) --}}
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @foreach($rows as $row)
                {{-- Check if the row is the Grand Total row using the Invoice No. field --}}
                <tr class="{{ $row['invoice_no'] === 'GRAND TOTAL' ? 'grand-total' : '' }}">

                    {{-- Loop through ALL defined keys to dynamically add columns --}}
                    @foreach($dataKeys as $key)
                        @php
                            $value = $row[$key] ?? '';
                            // Identify numeric/currency columns
                            $isCurrency = in_array($key, ['total_trip_amount', 'driver_earning']) ||
                                           ($key !== 'invoice_no' && $key !== 'payment_date');
                        @endphp

                        @if($isCurrency && $key !== 'invoice_no')
                            <td class="numeric">
                                @if($value !== '' && $value !== null)
                                    {{-- Format the numeric value as currency --}}
                                    ₱{{ number_format((float)$value, 2) }}
                                @endif
                            </td>
                        @else
                            {{-- Standard text columns (Invoice No. and Date/Time) --}}
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
