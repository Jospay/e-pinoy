<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\EWallet;
use App\Models\TransactionHistory;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $filter = $request->query('status', 'completed');
        $now = Carbon::now('Asia/Manila');

        $transactions = Reservation::with(['fromStation', 'toStation', 'status', 'vehicle'])
            ->where('passenger_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($item) use ($now) {
                $statusName = $item->status->name ?? 'Pending';
                $lowerStatus = strtolower($statusName);

                $isCompleted = str_contains($lowerStatus, 'completed');
                $isPaid = str_contains($lowerStatus, 'paid') && !$isCompleted;
                $isRefunded = str_contains($lowerStatus, 'refund');
                $isPending = !$isPaid && !$isCompleted && !$isRefunded;

                // Time Logic
                $departureDateTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_from_time, 'Asia/Manila');
                $arrivalDateTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_to_time, 'Asia/Manila');
                if ($arrivalDateTime->lessThan($departureDateTime)) $arrivalDateTime->addDay();

                $tenMinsPastDept = $departureDateTime->copy()->addMinutes(10);
                $twoHrsPastArrival = $arrivalDateTime->copy()->addHours(2);

                $canRefund = $isPaid && $now->greaterThanOrEqualTo($tenMinsPastDept) && $now->lessThanOrEqualTo($twoHrsPastArrival);
                $isExpired = $isPaid && $now->greaterThan($twoHrsPastArrival);
                $isTooEarly = $isPaid && $now->lessThan($tenMinsPastDept);

                return [
                    'id' => $item->id,
                    'from_bus_station_id' => $item->from_bus_station_id,
                    'qrcode_name' => $item->qrcode_name,
                    'origin' => $item->fromStation->name ?? 'N/A',
                    'destination' => $item->toStation->name ?? 'N/A',
                    'amount' => $item->amount,
                    'formatted_amount' => number_format($item->amount, 2),
                    'book_at' => Carbon::parse($item->reserve_date)->format('M d, Y'),
                    'time_window' => date('h:i A', strtotime($item->reserve_from_time)) . ' - ' . date('h:i A', strtotime($item->reserve_to_time)),
                    'status_text' => $statusName,
                    'is_paid' => $isPaid,
                    'is_pending' => $isPending,
                    'is_completed' => $isCompleted,
                    'is_refunded' => $isRefunded,
                    'can_refund' => $canRefund,
                    'is_expired' => $isExpired,
                    'is_too_early' => $isTooEarly,
                    'date_at' => $item->created_at->format('M d, Y'),
                    'passenger_count' => $item->passenger_count,
                    'vehicle_name' => $item->vehicle ? ($item->vehicle->model . ' (' . $item->vehicle->plate_number . ')') : 'N/A',
                ];
            });

        return Inertia::render('passenger/dashboard/TransactionHistory', [
            'transactions' => $transactions,
            'initialFilter' => $filter
        ]);
    }

    public function refund(Request $request, Reservation $reservation)
{
    $user = auth()->user();

    // 1. Get the specific status from your seeder
    $refundStatus = Status::where('name', 'refund')->first();

    if (!$refundStatus) {
        return back()->with('error', 'Refund status configuration missing.');
    }

    // 2. Authorization
    if ($reservation->passenger_id !== $user->id) {
        return back()->with('error', 'Unauthorized.');
    }

    try {
        DB::beginTransaction();

        // 3. Update Status
        $reservation->status_id = $refundStatus->id;
        $reservation->save();

        // 4. Update E-Wallet
        $wallet = EWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['amount' => 0]
        );

        // Lock to prevent race conditions
        $wallet = EWallet::where('id', $wallet->id)->lockForUpdate()->first();

        $oldBalance = (float) $wallet->amount;
        $refundValue = (float) $reservation->amount;
        $newBalance = $oldBalance + $refundValue;

        $wallet->amount = $newBalance;
        $wallet->save();

        // 5. Log History
        TransactionHistory::create([
            'e_wallet_id' => $wallet->id,
            'old_amount' => $oldBalance,
            'new_amount' => $newBalance,
            'type' => 'credit'
        ]);

        DB::commit();
        return back();

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Refund Error: ' . $e->getMessage());
        return back()->with('error', 'Process failed: ' . $e->getMessage());
    }
}
}
