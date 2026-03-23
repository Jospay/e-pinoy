<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\EWallet;
use App\Models\Status;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

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

                // Consolidate "Failed" statuses for the UI
                $displayStatus = $statusName;
                if (in_array($statusName, ['Cancelled', 'Expired', 'Failed'])) {
                    $displayStatus = 'Failed';
                }

                return [
                    'id' => $item->id,
                    'amount' => number_format(abs($change), 2),
                    'symbol' => $change >= 0 ? '+' : '-',
                    'balance' => number_format($item->new_amount, 2),
                    'date' => $item->created_at->format('M d, Y'),
                    'time' => $item->created_at->format('h:i A'),
                    'description' => $item->description,
                    'status' => $displayStatus,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                ];
            });
    }

    private function getStatusId($name)
    {
        $status = Status::where('name', $name)->first();
        return $status ? $status->id : null;
    }

    public function createLoadSession(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);
        $user = auth()->user();
        $pendingStatusId = $this->getStatusId('Pending');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $successUrl = route('passenger.wallet.success', ['userId' => $user->id]) . "?session_id={CHECKOUT_SESSION_ID}";

        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => ['name' => $user->name, 'email' => $user->email],
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'success_url' => $successUrl,
                    'cancel_url'  => route('passenger.mywallet'),
                    'line_items'  => [[
                        'name'     => 'E-Pinoy Wallet Top-up',
                        'amount'   => (int)($request->amount * 100),
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

        $sessionData = $response->json()['data'];
        $wallet = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);

        TransactionHistory::create([
            'e_wallet_id' => $wallet->id,
            'status_id'   => $pendingStatusId,
            'old_amount'  => $wallet->amount,
            'new_amount'  => $wallet->amount,
            'type'        => 'credit',
            'description' => 'Wallet Top-up (Failed)',
            'paymongo_checkout_session_id' => $sessionData['id'],
            'latitude'    => $latitude,
            'longitude'   => $longitude
        ]);

        return Inertia::location($sessionData['attributes']['checkout_url']);
    }

    public function loadSuccess(Request $request, $userId = null)
    {
        $sessionIdFromUrl = $request->query('session_id');
        $userId = $userId ?? auth()->id();

        try {
            DB::beginTransaction();

            $transaction = TransactionHistory::where('paymongo_checkout_session_id', $sessionIdFromUrl)
                ->where('status_id', $this->getStatusId('Pending'))
                ->first();

            if (!$transaction) {
                DB::rollBack();
                return redirect()->route('passenger.mywallet')->with('error', 'No pending transaction found.');
            }

            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->get("https://api.paymongo.com/v1/checkout_sessions/{$transaction->paymongo_checkout_session_id}");

            if (!$response->successful()) throw new \Exception("PayMongo API error.");

            $attributes = $response->json()['data']['attributes'];
            $paymongoStatus = $attributes['status'] ?? 'open';

            $isPaid = ($paymongoStatus === 'completed');
            if (!$isPaid && !empty($attributes['payments'])) {
                foreach ($attributes['payments'] as $payment) {
                    if (($payment['attributes']['status'] ?? '') === 'paid') {
                        $isPaid = true;
                        break;
                    }
                }
            }

            if ($isPaid) {
                $wallet = EWallet::where('user_id', $userId)->lockForUpdate()->first();
                $topUpAmount = $attributes['line_items'][0]['amount'] / 100;
                $oldAmount = (float) $wallet->amount;
                $newAmount = $oldAmount + $topUpAmount;

                $wallet->updateAmountAndSeal($newAmount);

                $transaction->update([
                    'status_id'   => $this->getStatusId('Paid'),
                    'old_amount'  => $oldAmount,
                    'new_amount'  => $newAmount,
                    'description' => 'Wallet Top-up via PayMongo',
                ]);

                DB::commit();
                return redirect()->route('passenger.mywallet')->with('success', '₱' . number_format($topUpAmount, 2) . ' loaded successfully!');
            }

            if (in_array($paymongoStatus, ['expired', 'cancelled'])) {
                $transaction->update(['status_id' => $this->getStatusId('Failed')]);
                DB::commit();
                return redirect()->route('passenger.mywallet')->with('error', 'Payment was ' . $paymongoStatus);
            }

            DB::rollBack();
            return redirect()->route('passenger.mywallet')->with('info', 'Payment is still processing.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('passenger.mywallet')->with('error', 'Error processing payment.');
        }
    }
}
