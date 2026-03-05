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
                    'date' => Carbon::parse($item->reserve_date)->format('M d, Y'),
                    'time_window' => date('h:i A', strtotime($item->reserve_from_time)) . ' - ' . date('h:i A', strtotime($item->reserve_to_time)),
                    'status_text' => $statusName,
                    'is_paid' => $isPaid,
                    'is_pending' => $isPending,
                    'is_completed' => $isCompleted,
                    'is_refunded' => $isRefunded,
                    'can_refund' => $canRefund,
                    'is_expired' => $isExpired,
                    'is_too_early' => $isTooEarly,
                    'booked_at' => $item->created_at->format('M d, Y'),
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

        $now = Carbon::now('Asia/Manila');

        // 1. Improved Status lookup
        $refundStatus = Status::where('name', 'Refunded')
                        ->orWhere('name', 'Refund')
                        ->first();

        if (!$refundStatus) {
            return back()->with('error', 'Refund status not found in database. Please contact admin.');
        }

        // 2. Validate ticket ownership and eligibility
        if ($reservation->passenger_id !== $user->id) {
            return back()->with('error', 'Unauthorized refund attempt.');
        }

        // 3. Time validation
        $departureDateTime = Carbon::parse($reservation->reserve_date . ' ' . $reservation->reserve_from_time, 'Asia/Manila');
        $arrivalDateTime = Carbon::parse($reservation->reserve_date . ' ' . $reservation->reserve_to_time, 'Asia/Manila');
        if ($arrivalDateTime->lessThan($departureDateTime)) $arrivalDateTime->addDay();

        $tenMinsPastDept = $departureDateTime->copy()->addMinutes(10);
        $twoHrsPastArrival = $arrivalDateTime->copy()->addHours(2);

        if ($now->lessThan($tenMinsPastDept)) {
            return back()->with('error', 'Refund window is not yet open.');
        }

        if ($now->greaterThan($twoHrsPastArrival)) {
            return back()->with('error', 'Refund window has already expired.');
        }

        try {
            DB::beginTransaction();

            // 4. Update Reservation Status
            $reservation->update(['status_id' => $refundStatus->id]);

            // 5. Update or Create E-Wallet
            $wallet = EWallet::firstOrCreate(
                ['user_id' => $user->id],
                ['amount' => 0]
            );

            $oldAmount = $wallet->amount;
            $refundAmount = $reservation->amount;
            $newAmount = $oldAmount + $refundAmount;

            $wallet->update(['amount' => $newAmount]);

            // 6. Log the transaction history
            TransactionHistory::create([
                'e_wallet_id' => $wallet->id,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'type' => 'credit'
            ]);

            DB::commit();
            return back()->with('success', '₱' . number_format($refundAmount, 2) . ' has been refunded to your E-Wallet.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Log the actual error for the developer
            Log::error('Refund Error: ' . $e->getMessage());

            // Return specific error to frontend so you know why it failed
            return back()->with('error', 'Refund failed: ' . $e->getMessage());
        }
    }
}
