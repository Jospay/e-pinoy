@php
    use Carbon\Carbon;
@endphp

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
            margin-bottom: 20px;
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
            @if($source === 'show')
                <th>Driver</th>
                <th>Invoice No.</th>
                <th>Date</th>
                <th>Amount</th>
            @else
                <th>Franchise</th>
                <th>Date</th>
                <th>Amount</th>
            @endif
        </tr>
    </thead>

    <tbody>
        @foreach($rows as $row)
            <tr>
                @if($source === 'show')
                    <td>{{ $row->driver->user->username ?? 'N/A' }}</td>
                    <td>{{ $row->invoice_no }}</td>
                    <td>{{ Carbon::parse($row->payment_date)->format('M j, Y h:i A') }}</td>
                    <td class="amount">₱{{ number_format($row->amount, 2) }}</td>
                @else
                    <td>{{ $row->{$tab . '_name'} ?? 'N/A' }}</td>
                    <td>
                        @if(isset($row->month_name)) {{ $row->month_name }}
                        @elseif(isset($row->week_start)) {{ date('M j', strtotime($row->week_start)) }} - {{ date('M j, Y', strtotime($row->week_end)) }}
                        @else {{ date('M j, Y', strtotime($row->payment_date)) }}
                        @endif
                    </td>
                    <td class="amount">₱{{ number_format($row->total_amount, 2) }}</td>
                @endif
            </tr>
        @endforeach

        {{-- Grand Total Row --}}
        <tr class="grand-total">
            <td colspan="{{ $source === 'show' ? 3 : 2 }}" style="text-align: right;">GRAND TOTAL</td>
            <td class="amount">
                ₱{{ number_format($rows->sum($source === 'show' ? 'amount' : 'total_amount'), 2) }}
            </td>
        </tr>
    </tbody>
</table>

</body>
</html>