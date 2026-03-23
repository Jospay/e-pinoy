<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\EWallet;
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

        $reservations = Reservation::with([
                'fromStation',
                'toStation',
                'status',
                'vehicle',
                'taxiReservations.status',
                'taxiReservations.vehicle'
            ])
            ->where('passenger_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $allTransactions = collect();

        foreach ($reservations as $item) {
            $statusName = $item->status->name ?? 'Pending';
            $lowerStatus = strtolower($statusName);
            $isCompleted = str_contains($lowerStatus, 'completed');
            $isPaid = str_contains($lowerStatus, 'paid') && !$isCompleted;
            $isRefunded = str_contains($lowerStatus, 'refund');
            $isPending = !$isPaid && !$isCompleted && !$isRefunded;

            $departureDateTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_from_time, 'Asia/Manila');
            $endOfDayExpiry = Carbon::parse($item->reserve_date, 'Asia/Manila')->endOfDay();
            $tenMinsPastDept = $departureDateTime->copy()->addMinutes(10);

            $canRefundBus = $isPaid && $now->greaterThanOrEqualTo($tenMinsPastDept) && $now->lessThanOrEqualTo($endOfDayExpiry);
            $isBusExpired = $now->greaterThan($endOfDayExpiry) && ($isPaid || $isPending);

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
                'can_refund' => $canRefundBus,
                'is_expired' => $isBusExpired,
                'date_at' => $item->created_at->format('M d, Y'),
                'created_at_raw' => $item->created_at,
                'vehicle_name' => $item->vehicle ? ($item->vehicle->model . ' (' . $item->vehicle->plate_number . ')') : 'N/A',
                'passenger_count' => $item->passenger_count,
                'from_bus_station_id' => $item->from_bus_station_id,
            ]);

            if ($item->taxiReservations && $item->taxiReservations->count() > 0) {
                foreach ($item->taxiReservations as $taxi) {
                    $tStatus = $taxi->status->name ?? 'Pending';
                    $tLower = strtolower($tStatus);
                    $isPaidStatus = str_contains($tLower, 'paid');
                    $isTaxiCompleted = str_contains($tLower, 'completed');
                    $isTaxiRefunded = str_contains($tLower, 'refund');
                    $isTaxiExpired = $now->greaterThan($endOfDayExpiry);
                    $canShowTaxiActions = $isPaidStatus && !$isTaxiCompleted && !$isTaxiExpired;

                    $formattedPickupTime = 'Pending Payment';
                    if ($isPaidStatus || $isTaxiCompleted) {
                        if ($taxi->time_pickup) {
                            $formattedPickupTime = Carbon::parse($taxi->time_pickup)->format('h:i A');
                        } else {
                            $busArrivalTime = Carbon::parse($item->reserve_date . ' ' . $item->reserve_to_time, 'Asia/Manila');
                            $formattedPickupTime = $now->greaterThanOrEqualTo($busArrivalTime->copy()->subMinutes(30))
                                ? "Est. Pickup: " . $busArrivalTime->format('h:i A')
                                : "Wait for bus arrival";
                        }
                    }

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
                        'is_paid' => $isPaidStatus,
                        'is_pending' => str_contains($tLower, 'pending'),
                        'is_completed' => $isTaxiCompleted,
                        'is_refunded' => $isTaxiRefunded,
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
