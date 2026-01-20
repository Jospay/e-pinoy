<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>

    <style>
    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        margin: 20px;
    }

    h2 {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        border: 1px solid #777;
        padding: 8px;
    }

    th {
        background: #f0f0f0;
        font-weight: bold;
        text-align: center;
    }

    td.amount {
        text-align: right;
    }

    .grand-total {
        font-weight: bold;
        background: #e8e8e8;
    }
    </style>

</head>
<body>

<h2>{{ $title }}</h2>

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
            {{-- The last row contains the GRAND TOTAL --}}
            <tr class="{{ $loop->last ? 'grand-total' : '' }}">

                {{-- Loop through ALL defined keys to dynamically add columns --}}
                @foreach($dataKeys as $key)
                {{-- Check if the current column is a numeric amount column --}}
                @php
                    // Ensure 'driver_earning' is included in the list of keys to be formatted as currency
                    $isNumeric = in_array($key, ['amount', 'driver_earning', 'tax', 'bank', 'markup_fee', 'system_fee']);
                    // Check if the key exists in the current row before displaying
                    $value = $row[$key] ?? '';
                @endphp

                @if($isNumeric)
                    <td class="numeric">
                        {{-- Only display currency format if a value is present --}}
                        @if($value !== '' && $value !== null)
                            ₱{{ number_format((float)$value, 2) }}
                        @endif
                    </td>
                @else
                        {{-- Standard text columns --}}
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
