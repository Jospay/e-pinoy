<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\EWallet;
use App\Models\TransactionHistory;
use App\Models\Status;
use App\Models\TaxiReservation;
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
        $statusFilter = $request->query('status', 'completed');
        $typeFilter = $request->query('type', 'all'); // 'all', 'bus', 'taxi'
        $now = Carbon::now('Asia/Manila');

        $reservations = Reservation::with(['fromStation', 'toStation', 'status', 'vehicle', 'taxiReservation.status'])
            ->where('passenger_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $allTransactions = collect();

        foreach ($reservations as $item) {
            // --- 1. PROCESS BUS TRANSACTION ---
            $statusName = $item->status->name ?? 'Pending';
            $lowerStatus = strtolower($statusName);

            $isCompleted = str_contains($lowerStatus, 'completed');
            $isPaid = str_contains($lowerStatus, 'paid') && !$isCompleted;
            $isRefunded = str_contains($lowerStatus, 'refund');
            $isPending = !$isPaid && !$isCompleted && !$isRefunded;

            $departureDateTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_from_time, 'Asia/Manila');
            $arrivalDateTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_to_time, 'Asia/Manila');
            if ($arrivalDateTime->lessThan($departureDateTime)) $arrivalDateTime->addDay();

            $tenMinsPastDept = $departureDateTime->copy()->addMinutes(10);
            $twoHrsPastArrival = $arrivalDateTime->copy()->addHours(2);

            // Logic: Can refund only if Paid AND (Current time is between 10m post-dept and 2h post-arrival)
            $canRefund = $isPaid && $now->greaterThanOrEqualTo($tenMinsPastDept) && $now->lessThanOrEqualTo($twoHrsPastArrival);
            $isExpired = $now->greaterThan($twoHrsPastArrival) && ($isPaid || $isPending);

            $allTransactions->push([
                'id' => $item->id,
                'type' => 'bus',
                'qrcode_name' => $item->qrcode_name,
                'origin' => $item->fromStation->name ?? 'N/A',
                'destination' => $item->toStation->name ?? 'N/A',
                'amount' => (float)$item->amount,
                'book_at' => Carbon::parse($item->reserve_date)->format('M d, Y'),
                'time_window' => date('h:i A', strtotime($item->reserve_from_time)) . ' - ' . date('h:i A', strtotime($item->reserve_to_time)),
                'status_text' => $statusName,
                'is_paid' => $isPaid,
                'is_pending' => $isPending,
                'is_completed' => $isCompleted,
                'is_refunded' => $isRefunded,
                'can_refund' => $canRefund,
                'is_expired' => $isExpired,
                'date_at' => $item->created_at->format('M d, Y'),
                'vehicle_name' => $item->vehicle ? ($item->vehicle->model . ' (' . $item->vehicle->plate_number . ')') : 'N/A',
                'passenger_count' => $item->passenger_count,
                'from_bus_station_id' => $item->from_bus_station_id,
            ]);

            // --- 2. PROCESS TAXI TRANSACTION (If exists) ---
            if ($item->taxiReservation) {
                $taxi = $item->taxiReservation;
                $tStatus = $taxi->status->name ?? 'Pending';
                $tLower = strtolower($tStatus);

                $allTransactions->push([
                    'id' => $taxi->id,
                    'parent_res_id' => $item->id,
                    'type' => 'taxi',
                    'qrcode_name' => $taxi->qrcode_name,
                    'origin' => $item->toStation->name ?? 'Bus Terminal',
                    'destination' => $taxi->destination_loc_name,
                    'amount' => (float)$taxi->amount,
                    'book_at' => Carbon::parse($item->reserve_date)->format('M d, Y'),
                    'time_window' => 'Post-Arrival Pickup',
                    'status_text' => $tStatus,
                    'is_paid' => str_contains($tLower, 'paid'),
                    'is_pending' => str_contains($tLower, 'pending'),
                    'is_completed' => str_contains($tLower, 'completed'),
                    'is_refunded' => str_contains($tLower, 'refund'),
                    'can_refund' => $canRefund, // Taxi follows Bus refund window
                    'is_expired' => $isExpired,
                    'date_at' => $taxi->created_at->format('M d, Y'),
                ]);
            }
        }

        return Inertia::render('passenger/dashboard/TransactionHistory', [
            'transactions' => $allTransactions,
            'initialFilter' => $statusFilter,
            'initialType' => $typeFilter
        ]);
    }

    public function refund(Request $request, $id)
    {
        // Note: The $id here refers to the Bus Reservation ID
        $user = auth()->user();
        $reservation = Reservation::where('id', $id)->where('passenger_id', $user->id)->firstOrFail();
        $refundStatus = Status::where('name', 'refund')->first();

        try {
            DB::beginTransaction();

            $refundTotal = 0;

            // Refund Bus
            if (!str_contains(strtolower($reservation->status->name), 'refund')) {
                $reservation->status_id = $refundStatus->id;
                $reservation->save();
                $refundTotal += (float)$reservation->amount;
            }

            // Refund Taxi
            if ($reservation->taxiReservation) {
                $taxi = $reservation->taxiReservation;
                if (!str_contains(strtolower($taxi->status->name), 'refund')) {
                    $taxi->status_id = $refundStatus->id;
                    $taxi->save();
                    $refundTotal += (float)$taxi->amount;
                }
            }

            $wallet = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
            $wallet = EWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $oldBalance = (float)$wallet->amount;
            $wallet->amount += $refundTotal;
            $wallet->save();

            TransactionHistory::create([
                'e_wallet_id' => $wallet->id,
                'old_amount' => $oldBalance,
                'new_amount' => $wallet->amount,
                'type' => 'credit'
            ]);

            DB::commit();
            return back()->with('success', 'Refund of ₱' . number_format($refundTotal, 2) . ' processed.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Refund failed.');
        }
    }
}
