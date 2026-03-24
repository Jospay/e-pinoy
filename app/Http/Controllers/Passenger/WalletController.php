<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\EWallet;
use App\Models\Status;
use App\Models\TransactionHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $wallet = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
        $transactions = $this->getPaginatedTransactions($wallet->id);

        return Inertia::render('passenger/dashboard/MyWallet', [
            'walletBalance' => (string) $wallet->amount,
            'transactions' => [
                'data' => $transactions->items(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    public function infiniteTransactions(Request $request)
    {
        $user = Auth::user();
        $wallet = EWallet::where('user_id', $user->id)->first();
        if (!$wallet) return response()->json(['data' => [], 'current_page' => 1, 'last_page' => 1]);

        $transactions = $this->getPaginatedTransactions($wallet->id);
        return response()->json([
            'data' => $transactions->items(),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
        ]);
    }

    private function getPaginatedTransactions($walletId)
    {
        return TransactionHistory::where('e_wallet_id', $walletId)
            ->with('status')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($item) {
                $change = $item->new_amount - $item->old_amount;
                $statusName = $item->status->name ?? 'Unknown';
                $displayStatus = in_array($statusName, ['Cancelled', 'Expired', 'Failed']) ? 'Failed' : $statusName;

                return [
                    'id' => $item->id,
                    'amount' => number_format(abs($change), 2),
                    'symbol' => $change >= 0 ? '+' : '-',
                    'balance' => number_format($item->new_amount, 2),
                    'date' => $item->created_at->format('M d, Y'),
                    'time' => $item->created_at->format('h:i A'),
                    'description' => $item->description,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'status' => $displayStatus,
                ];
            });
    }

    public function createLoadSession(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = auth()->user();
        $walletRecord = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);
        $lastVerified = $walletRecord->last_otp_verified_at;

        // Security Check: If never verified or verified > 7 days ago
        if (!$lastVerified || Carbon::parse($lastVerified)->addDays(7)->isPast()) {

            // 1. Store load details in session to resume later
            Session::put('pending_wallet_amount', $request->amount);
            Session::put('pending_wallet_lat', $request->latitude);
            Session::put('pending_wallet_lng', $request->longitude);

            // 2. Trigger OTP Send via the OTPController
            $otpController = new OTPController();
            $otpResponse = $otpController->sendOtp($request);

            // 3. Redirect to the verification screen
            return redirect()->route('passenger.otp.index', ['purpose' => 'wallet'])
                           ->with('success', 'Security verification required to proceed with this top-up.');
        }

        // Already verified within 7 days, go straight to PayMongo
        return $this->executePayMongoCheckout($request->amount, $request->latitude, $request->longitude);
    }

    /**
     * Resumes the wallet load process after a successful OTP verification.
     * This is called by the redirect in OTPController@verifyOtp.
     */
    public function resumeAfterOtp(Request $request)
    {
        $amount = Session::get('pending_wallet_amount');
        // Prefer current request location, fallback to session location
        $lat = $request->latitude ?? Session::get('pending_wallet_lat');
        $lng = $request->longitude ?? Session::get('pending_wallet_lng');

        if (!$amount) {
            return redirect()->route('passenger.mywallet')->withErrors(['amount' => 'Top-up session expired. Please try again.']);
        }

        // Clean up session before proceeding
        Session::forget(['pending_wallet_amount', 'pending_wallet_lat', 'pending_wallet_lng']);

        return $this->executePayMongoCheckout($amount, $lat, $lng);
    }

    public function executePayMongoCheckout($amount, $lat = null, $lng = null)
    {
        $user = auth()->user();
        try {
            return DB::transaction(function () use ($amount, $lat, $lng, $user) {
                $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();

                // Check integrity seal
                if (!$wallet->isVerified()) {
                    Log::emergency("TOP-UP BLOCKED: Tampered wallet seal for User ID: " . $user->id);
                    throw new \Exception("Wallet integrity check failed. For your security, this transaction has been blocked. Please contact support.");
                }

                $payload = [
                    'data' => [
                        'attributes' => [
                            'billing' => ['name' => $user->name, 'email' => $user->email],
                            'send_email_receipt' => true,
                            'show_description' => true,
                            'success_url' => route('passenger.wallet.success', ['userId' => $user->id]),
                            'cancel_url'  => route('passenger.mywallet'),
                            'line_items'  => [[
                                'name'     => 'E-Pinoy Wallet Top-up',
                                'amount'   => (int)($amount * 100), // PayMongo uses centavos
                                'currency' => 'PHP',
                                'quantity' => 1,
                            ]],
                            'payment_method_types' => ['card', 'paymaya', 'qrph', 'grab_pay', 'gcash'],
                            'description' => 'Wallet Load for ' . $user->name,
                        ],
                    ],
                ];

                $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                    ->post('https://api.paymongo.com/v1/checkout_sessions', $payload);

                if ($response->successful()) {
                    $sessionData = $response->json()['data'];

                    // Create a pending transaction history entry
                    TransactionHistory::create([
                        'e_wallet_id' => $wallet->id,
                        'status_id'   => Status::where('name', 'Pending')->first()?->id ?? 1,
                        'old_amount'  => $wallet->amount,
                        'new_amount'  => $wallet->amount,
                        'type'        => 'credit',
                        'description' => 'Wallet Top-up (Initiated)',
                        'paymongo_checkout_session_id' => $sessionData['id'],
                        'latitude'    => $lat,
                        'longitude'   => $lng
                    ]);

                    // Use Inertia::location to perform a full window redirect to the PayMongo URL
                    return Inertia::location($sessionData['attributes']['checkout_url']);
                }

                Log::error("PayMongo Session Error: " . $response->body());
                throw new \Exception("Unable to initiate payment session with PayMongo.");
            });
        } catch (\Exception $e) {
            return redirect()->route('passenger.mywallet')->withErrors(['amount' => $e->getMessage()]);
        }
    }

    public function loadSuccess(Request $request, $userId = null)
    {
        $userId = $userId ?? auth()->id();
        try {
            DB::beginTransaction();

            $pendingStatusId = Status::where('name', 'Pending')->first()?->id;

            $transaction = TransactionHistory::whereHas('eWallet', function($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->where('status_id', $pendingStatusId)
                ->where('type', 'credit')
                ->whereNotNull('paymongo_checkout_session_id')
                ->latest()
                ->first();

            if (!$transaction) {
                DB::rollBack();
                return redirect()->route('passenger.mywallet')->with('error', 'No pending payment session found.');
            }

            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->get("https://api.paymongo.com/v1/checkout_sessions/{$transaction->paymongo_checkout_session_id}");

            if ($response->failed()) throw new \Exception("Could not verify payment status.");

            $attributes = $response->json()['data']['attributes'];
            $isPaid = ($attributes['payment_intent']['attributes']['status'] ?? null) === 'succeeded'
                      || $attributes['status'] === 'completed';

            if ($isPaid) {
                $wallet = EWallet::where('id', $transaction->e_wallet_id)->lockForUpdate()->first();
                $topUpAmount = $attributes['line_items'][0]['amount'] / 100;
                $oldAmount = (float) $wallet->amount;
                $newAmount = $oldAmount + $topUpAmount;

                $wallet->updateAmountAndSeal($newAmount);

                $transaction->update([
                    'status_id'   => Status::where('name', 'Paid')->first()?->id ?? 2,
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'description' => 'Wallet Top-up via PayMongo (Successful)',
                ]);

                DB::commit();

                // Success message is flashed here
                return redirect()->route('passenger.mywallet')
                    ->with('success', '₱' . number_format($topUpAmount, 2) . ' successfully loaded!');
            }

            DB::rollBack();
            return redirect()->route('passenger.mywallet')->with('info', 'Your payment is still being processed.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Top-up Verification Failed: " . $e->getMessage());
            return redirect()->route('passenger.mywallet')->with('error', 'Failed to update wallet balance.');
        }
    }
}
