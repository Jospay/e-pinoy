<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\EWallet;
use App\Models\Revenue;
use App\Models\TaxiReservation;
use App\Models\TransactionHistory;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class TransactionHistoryController extends Controller
{
    public function index(Request $request)
    {
    $user = auth()->user();
    $statusFilter = $request->query('status', 'completed');
    $typeFilter = $request->query('type', 'all');
    $now = Carbon::now('Asia/Manila');

    $allTransactions = collect();

   if ($statusFilter === 'completed') {
    // 1. COMPLETED TAB: Fetch from Revenues with the linked Route
    $revenues = Revenue::with(['status', 'vehicleType', 'route.vehicle'])
        ->where('passenger_id', $user->id)
        ->whereHas('status', function ($q) {
            $q->where('name', 'like', '%paid%')
              ->orWhere('name', 'like', '%completed%');
        })
        ->orderBy('created_at', 'desc')
        ->get();

    foreach ($revenues as $rev) {
        $vType = strtolower($rev->vehicleType->name ?? '');
        $isBus = str_contains($vType, 'bus');

        // Apply type filter
        if ($typeFilter !== 'all') {
            if ($typeFilter === 'bus' && !$isBus) continue;
            if ($typeFilter === 'taxi' && $isBus) continue;
        }

        // Access the route data
        $route = $rev->route;

        $allTransactions->push([
            'id' => $rev->id,
            'type' => $isBus ? 'bus' : 'taxi',
            'qrcode_name' => $rev->invoice_no,
            'origin' => $route->pickup_loc_name ?? 'Completed Trip',
            'destination' => $route->destination_loc_name ?? $rev->service_type,
            'amount' => (float)$rev->amount,
            'book_at' => Carbon::parse($rev->payment_date ?? $rev->created_at)->format('M d, Y'),
            'time_window' => $route && $route->start_trip
                                ? Carbon::parse($route->start_trip)->format('h:i A')
                                : 'Completed',
            'status_text' => 'Completed',
            'is_paid' => true,
            'is_pending' => false,
            'is_completed' => true,
            'is_refunded' => false,
            'can_refund' => false,
            'is_expired' => false,
            'date_at' => $rev->created_at->format('M d, Y'),
            'created_at_raw' => $rev->created_at,
            'vehicle_name' => $route->vehicle ? ($route->vehicle->model . ' (' . $route->vehicle->plate_number . ')') : 'N/A',
            'passenger_count' => $route->passenger_count ?? '0',
            'from_bus_station_id' => $route->id ?? null,
        ]);
    }
} else {
    $reservations = Reservation::with([
        'fromStation', 'toStation', 'status', 'vehicle',
        'taxiReservations.status', 'taxiReservations.vehicle'
    ])
    ->where('passenger_id', $user->id)
    ->orderBy('created_at', 'desc')
    ->get();

    foreach ($reservations as $item) {
        $statusName = $item->status->name ?? 'Pending';
        $lowerStatus = strtolower($statusName);

        // --- BUS STATUS LOGIC ---
        $isBusRefunded = str_contains($lowerStatus, 'refund');
        $isBusCompleted = str_contains($lowerStatus, 'completed');
        $isBusPaid = str_contains($lowerStatus, 'paid') && !$isBusCompleted && !$isBusRefunded;
        $isBusPending = !$isBusPaid && !$isBusRefunded && !$isBusCompleted;

        // --- DETERMINING IF WE SHOULD EVEN PROCESS THIS ROW ---
        // We only skip if BOTH the bus and ALL taxis fail the filter.
        $hasMatchingTaxi = $item->taxiReservations->some(function($t) use ($statusFilter) {
            $tLower = strtolower($t->status->name ?? '');
            if ($statusFilter === 'paid') return str_contains($tLower, 'paid');
            if ($statusFilter === 'pending') return str_contains($tLower, 'pending');
            if ($statusFilter === 'refund') return str_contains($tLower, 'refund');
            return false;
        });

        $busMatchesFilter = false;
        if ($statusFilter === 'paid' && $isBusPaid) $busMatchesFilter = true;
        if ($statusFilter === 'pending' && $isBusPending) $busMatchesFilter = true;
        if ($statusFilter === 'refund' && $isBusRefunded) $busMatchesFilter = true;

        // If neither the bus nor the taxi matches what the user clicked, skip to next reservation
        if (!$busMatchesFilter && !$hasMatchingTaxi) continue;

        // Date calculations for Bus
        $departureDateTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_from_time, 'Asia/Manila');
        $endOfDayExpiry = Carbon::parse($item->reserve_date, 'Asia/Manila')->endOfDay();
        $tenMinsPastDept = $departureDateTime->copy()->addMinutes(10);
        $canRefundBus = $isBusPaid && $now->greaterThanOrEqualTo($tenMinsPastDept) && $now->lessThanOrEqualTo($endOfDayExpiry);
        $isBusExpired = $now->greaterThan($endOfDayExpiry) && ($isBusPaid || $isBusPending);

        // 1. ADD BUS (Only if it matches filter and type)
        if ($busMatchesFilter && ($typeFilter === 'all' || $typeFilter === 'bus')) {
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
                'is_paid' => $isBusPaid,
                'is_pending' => $isBusPending,
                'is_completed' => false,
                'is_refunded' => $isBusRefunded,
                'can_refund' => $canRefundBus,
                'is_expired' => $isBusExpired,
                'date_at' => $item->created_at->format('M d, Y'),
                'created_at_raw' => $item->created_at,
                'vehicle_name' => $item->vehicle ? ($item->vehicle->model . ' (' . $item->vehicle->plate_number . ')') : 'N/A',
                'passenger_count' => $item->passenger_count,
                'from_bus_station_id' => $item->from_bus_station_id,
            ]);
        }

        // 2. ADD TAXIS (Check each taxi individually against the filter)
        if ($item->taxiReservations && ($typeFilter === 'all' || $typeFilter === 'taxi')) {
            foreach ($item->taxiReservations as $taxi) {
                $tStatus = $taxi->status->name ?? 'Pending';
                $tLower = strtolower($tStatus);

                $tIsRefunded = str_contains($tLower, 'refund');
                $tIsCompleted = str_contains($tLower, 'completed');
                $tIsPaid = str_contains($tLower, 'paid') && !$tIsCompleted && !$tIsRefunded;
                $tIsPending = str_contains($tLower, 'pending');

                // Taxi specific filter check
                $taxiMatches = false;
                if ($statusFilter === 'paid' && $tIsPaid) $taxiMatches = true;
                if ($statusFilter === 'pending' && $tIsPending) $taxiMatches = true;
                if ($statusFilter === 'refund' && $tIsRefunded) $taxiMatches = true;

                if (!$taxiMatches) continue;

                $isTaxiExpired = $now->greaterThan($endOfDayExpiry);
                $canShowTaxiActions = $tIsPaid && !$isTaxiExpired;

                $formattedPickupTime = $tIsPaid
                    ? ($taxi->time_pickup ? Carbon::parse($taxi->time_pickup)->format('h:i A') : "Wait for bus arrival")
                    : 'Pending Payment';

                $allTransactions->push([
                    'id' => $taxi->id,
                    'parent_res_id' => $item->id,
                    'type' => 'taxi',
                    'qrcode_name' => $taxi->qrcode_name,
                    'origin' => $taxi->pickup_loc_name,
                    'destination' => $taxi->destination_loc_name,
                    'amount' => (float)$taxi->amount,
                    'book_at' => Carbon::parse($item->reserve_date)->format('M d, Y'),
                    'time_window' => $formattedPickupTime,
                    'status_text' => $tStatus,
                    'is_paid' => $tIsPaid,
                    'is_pending' => $tIsPending,
                    'is_completed' => false,
                    'is_refunded' => $tIsRefunded,
                    'can_refund' => $canShowTaxiActions,
                    'can_view_ticket' => $canShowTaxiActions,
                    'is_expired' => $isTaxiExpired,
                    'date_at' => $taxi->created_at->format('M d, Y'),
                    'created_at_raw' => $taxi->created_at,
                    'vehicle_name' => $taxi->vehicle_id
                        ? ($taxi->vehicle->model . ' (' . $taxi->vehicle->plate_number . ')')
                        : 'Searching for driver...',
                    'passenger_count' => $taxi->passenger_count,
                    'from_bus_station_id' => $item->from_bus_station_id,
                ]);
            }
        }
    }
}

    $sortedTransactions = $allTransactions->sortByDesc('created_at_raw')->values();

    return Inertia::render('passenger/dashboard/TransactionHistory', [
        'transactions' => $sortedTransactions,
        'initialFilter' => $statusFilter,
        'initialType' => $typeFilter
    ]);
}

    public function refund(Request $request, $id)
    {
        // Validate that GPS data is present
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'type' => 'required|string'
        ]);

        $user = auth()->user();
        $type = $request->input('type');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $refundStatus = Status::where('name', 'like', '%refund%')->first();

        if (!$refundStatus) {
            return back()->with('error', 'Refund status configuration missing.');
        }

        $walletRecord = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
        $lastVerified = $walletRecord->last_otp_verified_at;

        if (!$lastVerified || Carbon::parse($lastVerified)->addDays(7)->isPast()) {
            $otpController = new OTPController(); // Ensure correct path
            $otpController->sendOtp($request);
            return redirect()->route('passenger.otp.index')->with('requires_otp', true);
        }

        try {
            DB::beginTransaction();

            if ($type === 'taxi') {
                $model = TaxiReservation::where('id', $id)
                    ->whereHas('reservation', function($q) use ($user) {
                        $q->where('passenger_id', $user->id);
                    })->firstOrFail();
            } else {
                $model = Reservation::where('id', $id)
                    ->where('passenger_id', $user->id)
                    ->firstOrFail();
            }

            if ($model->status_id == $refundStatus->id) {
                throw new \Exception("This transaction has already been refunded.");
            }

            $refundTotal = (float)$model->amount;

            if ($refundTotal > 0) {
                $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();

                if (!$wallet->isVerified()) {
                    Log::emergency("REFUND BLOCKED: Tampered wallet seal for User ID: " . $user->id);
                    throw new \Exception("Wallet integrity check failed. For your security, this transaction has been blocked. Please contact support.");
                }

                // Update status
                $model->status_id = $refundStatus->id;
                $model->save();

                // Update Balance
                $oldBalance = (float)$wallet->amount;
                $newBalance = $oldBalance + $refundTotal;
                $wallet->amount = $newBalance;
                $wallet->save();

                // Log History with GPS
                TransactionHistory::create([
                    'e_wallet_id' => $wallet->id,
                    'status_id'  => $refundStatus->id,
                    'old_amount'  => $oldBalance,
                    'new_amount'  => $newBalance,
                    'type'        => 'credit',
                    'description' => 'Refund for ' . ucfirst($type) . ' Booking ID: ' . $model->qrcode_name,
                    'latitude'    => $latitude,
                    'longitude'   => $longitude
                ]);
            }

            DB::commit();
            return redirect()->route('passenger.transactionhisory', ['status' => 'refund'])
                            ->with('success', 'Refund successful! GPS location logged.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Refund Error: " . $e->getMessage());
            return back()->with('error', $e->getMessage());
        }
    }
}
