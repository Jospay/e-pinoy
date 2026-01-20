@php
    use Carbon\Carbon;
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    {{-- IMPORTANT: This meta tag helps dompdf recognize the encoding --}}
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>

    <style>
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10px;
        margin: 0;
    }
    h2 { 
        text-align: center; 
        margin-bottom: 20px; 
    }
    table { 
        width: 100%; 
        border-collapse: collapse; 
        table-layout: fixed; 
    }
    th, td { 
        border: 1px solid #999; 
        padding: 5px; 
        word-wrap: break-word; 
    }
    th { 
        background: #eee; 
        text-align: center; 
    }
    
    /* Specific styling for money columns */
    .money { 
        text-align: right; 
        white-space: nowrap; 
    }
    .grand-total { 
        font-weight: bold; 
        background: #e0e0e0; 
    }
    </style>
</head>
<body>

    <h2>{{ $title }}</h2>

    <table>
        <thead>
            <tr>
            @if(isset($isDetailView) && $isDetailView)
                <th>Invoice No.</th>
                <th>Date</th>
                <th>Trip Amount</th>
            @else
                <th>{{ ucfirst($tab) }}</th>
                <th>Driver</th>
                <th style="width: 15%">Date</th>
                <th>Total Amount</th>
            @endif

            @foreach($feeTypes as $type)
                <th>{{ $type['display'] }}</th>
            @endforeach
            <th>Driver Earn</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php
                    // Date Logic in Blade
                    $dateDisplay = 'N/A';
                    if (isset($row->month_name)) {
                         $dateDisplay = $row->month_name;
                    } elseif (isset($row->week_start)) {
                         $start = Carbon::parse($row->week_start);
                         $end = Carbon::parse($row->week_end);
                         $dateDisplay = $start->format('M j') . ' - ' . $end->format('M j, Y');
                    } else {
                         $dateDisplay = Carbon::parse($row->payment_date)->format('M j, Y');
                    }
                @endphp
                <tr>
                    @if(isset($isDetailView) && $isDetailView)
                        <td>{{ $row->invoice_no }}</td>
                        <td>{{ Carbon::parse($row->payment_date)->format('M d, Y h:i A') }}</td>
                        <td class="money">&#8369;{{ number_format($row->total_amount, 2) }}</td>
                    @else
                        <td>{{ $row->franchise_name ?? '-' }}</td>
                        <td>{{ $row->driver_username }}</td>
                        <td>{{ $dateDisplay }}</td>
                        <td class="money">&#8369;{{ number_format($row->total_amount, 2) }}</td>
                    @endif
                    
                    {{-- Dynamic Fees --}}
                    @foreach($feeTypes as $type)
                        @php $key = 'total_' . $type['slug']; @endphp
                        <td class="money">&#8369;{{ number_format($row->$key ?? 0, 2) }}</td>
                    @endforeach

                    {{-- Driver Earning --}}
                    <td class="money">&#8369;{{ number_format($row->driver_earning, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        
        {{-- GRAND TOTAL FOOTER --}}
        <tfoot>
            <tr class="grand-total">
                <td colspan="{{ isset($isDetailView) && $isDetailView ? 2 : 3 }}" style="text-align: left;">GRAND TOTAL</td>
                
                {{-- Sum Total Amount --}}
                <td class="money">&#8369;{{ number_format($rows->sum('total_amount'), 2) }}</td>

                {{-- Sum Dynamic Fees --}}
                @foreach($feeTypes as $type)
                    @php $key = 'total_' . $type['slug']; @endphp
                    <td class="money">&#8369;{{ number_format($rows->sum($key), 2) }}</td>
                @endforeach

                {{-- Sum Driver Earning --}}
                <td class="money">&#8369;{{ number_format($rows->sum('driver_earning'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>