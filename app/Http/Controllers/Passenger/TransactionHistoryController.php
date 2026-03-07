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
        $filter = $request->query('status', 'completed');
        $now = Carbon::now('Asia/Manila');

        $transactions = Reservation::with(['fromStation', 'toStation', 'status', 'vehicle', 'taxiReservation.status'])
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

                $departureDateTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_from_time, 'Asia/Manila');
                $arrivalDateTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_to_time, 'Asia/Manila');
                if ($arrivalDateTime->lessThan($departureDateTime)) $arrivalDateTime->addDay();

                $tenMinsPastDept = $departureDateTime->copy()->addMinutes(10);
                $twoHrsPastArrival = $arrivalDateTime->copy()->addHours(2);

                $canRefund = $isPaid && $now->greaterThanOrEqualTo($tenMinsPastDept) && $now->lessThanOrEqualTo($twoHrsPastArrival);

                // Calculate total amount (Bus + Taxi) for the UI display
                $totalAmount = (float) $item->amount;
                if ($item->taxiReservation) {
                    $totalAmount += (float) $item->taxiReservation->amount;
                }

                return [
                    'id' => $item->id,
                    'from_bus_station_id' => $item->from_bus_station_id,
                    'qrcode_name' => $item->qrcode_name,
                    'origin' => $item->fromStation->name ?? 'N/A',
                    'destination' => $item->toStation->name ?? 'N/A',
                    'amount' => $totalAmount, // Showing total for refund context
                    'bus_amount' => $item->amount,
                    'formatted_amount' => number_format($totalAmount, 2),
                    'book_at' => Carbon::parse($item->reserve_date)->format('M d, Y'),
                    'time_window' => date('h:i A', strtotime($item->reserve_from_time)) . ' - ' . date('h:i A', strtotime($item->reserve_to_time)),
                    'status_text' => $statusName,
                    'is_paid' => $isPaid,
                    'is_pending' => $isPending,
                    'is_completed' => $isCompleted,
                    'is_refunded' => $isRefunded,
                    'can_refund' => $canRefund,
                    'date_at' => $item->created_at->format('M d, Y'),
                    'passenger_count' => $item->passenger_count,
                    'vehicle_name' => $item->vehicle ? ($item->vehicle->model . ' (' . $item->vehicle->plate_number . ')') : 'N/A',

                    'has_taxi' => $item->taxiReservation !== null,
                    'taxi_details' => $item->taxiReservation ? [
                        'id' => $item->taxiReservation->id,
                        'qrcode_name' => $item->taxiReservation->qrcode_name,
                        'pickup' => $item->taxiReservation->pickup_loc_name,
                        'destination' => $item->taxiReservation->destination_loc_name,
                        'amount' => $item->taxiReservation->amount,
                        'status' => $item->taxiReservation->status->name ?? 'Pending',
                    ] : null,
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
        $refundStatus = Status::where('name', 'refund')->first();

        if (!$refundStatus) {
            return back()->with('error', 'Refund status configuration missing.');
        }

        if ($reservation->passenger_id !== $user->id) {
            return back()->with('error', 'Unauthorized.');
        }

        try {
            DB::beginTransaction();

            // 1. Update Bus Reservation Status
            $reservation->status_id = $refundStatus->id;
            $reservation->save();

            $refundTotal = (float) $reservation->amount;

            if ($reservation->taxiReservation) {
                $taxi = $reservation->taxiReservation;
                $taxi->status_id = $refundStatus->id;
                $taxi->save();

                $refundTotal += (float) $taxi->amount;
            }

            // 3. Update E-Wallet
            $wallet = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
            $wallet = EWallet::where('id', $wallet->id)->lockForUpdate()->first();

            $oldBalance = (float) $wallet->amount;
            $newBalance = $oldBalance + $refundTotal;

            $wallet->amount = $newBalance;
            $wallet->save();

            // 4. Log History
            TransactionHistory::create([
                'e_wallet_id' => $wallet->id,
                'old_amount' => $oldBalance,
                'new_amount' => $newBalance,
                'type' => 'credit'
            ]);

            DB::commit();
            return back()->with('success', 'Refund of ₱' . number_format($refundTotal, 2) . ' processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund Error: ' . $e->getMessage());
            return back()->with('error', 'Process failed: ' . $e->getMessage());
        }
    }
}
