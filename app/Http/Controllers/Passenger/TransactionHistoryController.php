<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Default the filter to 'completed' as requested
        $filter = $request->query('status', 'completed');

        $transactions = Reservation::with(['fromStation', 'toStation', 'status'])
            ->where('passenger_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) {
                $statusName = $item->status->name ?? 'Pending';
                $lowerStatus = strtolower($statusName);

                // Define logic for status categories
                $isCompleted = str_contains($lowerStatus, 'completed');
                $isPaid = str_contains($lowerStatus, 'paid') && !$isCompleted;
                $isPending = !$isPaid && !$isCompleted;

                return [
                    'id' => $item->id,
                    'from_bus_station_id' => $item->from_bus_station_id,
                    'qr_name' => $item->qrcode_name,
                    'origin' => $item->fromStation->name ?? 'N/A',
                    'destination' => $item->toStation->name ?? 'N/A',
                    'amount' => number_format($item->amount, 2),
                    'date' => $item->reserve_date,
                    'time_window' => date('h:i A', strtotime($item->reserve_from_time)) . ' - ' . date('h:i A', strtotime($item->reserve_to_time)),
                    'status_text' => $statusName,
                    'is_paid' => $isPaid,
                    'is_pending' => $isPending,
                    'is_completed' => $isCompleted,
                    'booked_at' => $item->created_at->format('M d, Y'),
                ];
            });

        return Inertia::render('passenger/dashboard/TransactionHistory', [
            'transactions' => $transactions,
            'initialFilter' => $filter
        ]);
    }
}
