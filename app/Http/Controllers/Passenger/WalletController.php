<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\EWallet;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $wallet = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);

        $transactions = $this->getPaginatedTransactions($wallet->id);

        return Inertia::render('passenger/dashboard/MyWallet', [
            'walletBalance' => number_format($wallet->amount, 2),
            'transactions' => [
                'data' => $transactions->items(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    /**
     * Shared logic to ensure identical data structure for Infinite Scroll
     */
    private function getPaginatedTransactions($walletId)
    {
        return TransactionHistory::where('e_wallet_id', $walletId)
            ->orderBy('created_at', 'desc')
            ->paginate(10) // Increased to 15 for better scroll experience
            ->through(function ($item) {
                $change = $item->new_amount - $item->old_amount;
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
                ];
            });
    }

    public function infiniteTransactions(Request $request)
    {
        $user = auth()->user();
        $wallet = EWallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json(['data' => [], 'current_page' => 1, 'last_page' => 1]);
        }

        $transactions = $this->getPaginatedTransactions($wallet->id);

        return response()->json([
            'data' => $transactions->items(),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
        ]);
    }

    public function createLoadSession(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
        $user = auth()->user();
        $amount = $request->amount;

        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => ['name' => $user->name, 'email' => $user->email],
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'success_url' => route('passenger.wallet.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'  => route('passenger.mywallet'),
                    'line_items'  => [[
                        'name'     => 'E-Pinoy Wallet Top-up',
                        'amount'   => (int)($amount * 100),
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

        if ($response->failed()) {
            return back()->withErrors(['amount' => 'PayMongo error. Please try again.']);
        }

        return Inertia::location($response->json()['data']['attributes']['checkout_url']);
    }

    public function loadSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');
        if (!$sessionId) return redirect()->route('passenger.mywallet');

        try {
            DB::beginTransaction();
            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->get("https://api.paymongo.com/v1/checkout_sessions/{$sessionId}");

            if ($response->successful()) {
                $data = $response->json()['data'];
                if ($data['attributes']['status'] === 'completed') {
                    $user = auth()->user();
                    $amount = $data['attributes']['line_items'][0]['amount'] / 100;

                    $wallet = EWallet::where('user_id', $user->id)->lockForUpdate()->first();
                    $oldAmount = $wallet->amount;
                    $newAmount = $oldAmount + $amount;

                    $wallet->updateAmountAndSeal($newAmount);

                    TransactionHistory::create([
                        'e_wallet_id' => $wallet->id,
                        'old_amount' => $oldAmount,
                        'new_amount' => $newAmount,
                        'type' => 'credit',
                        'description' => 'Wallet Top-up via Online Payment',
                    ]);

                    DB::commit();
                    return redirect()->route('passenger.mywallet')->with('success', 'Wallet loaded successfully!');
                }
            }
            DB::rollBack();
            return redirect()->route('passenger.mywallet')->with('error', 'Payment verification failed.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Log::error("Load Success Error: " . $e->getMessage());
            return redirect()->route('passenger.mywallet')->with('error', 'An error occurred during verification.');
        }
    }
}
