<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\EWallet;
use App\Models\TransactionHistory;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $wallet = EWallet::firstOrCreate(
            ['user_id' => $user->id],
            ['amount' => 0]
        );

        $transactions = TransactionHistory::where('e_wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($item) {
                $change = $item->new_amount - $item->old_amount;
                return [
                    'id' => $item->id,
                    'amount' => number_format(abs($change), 2),
                    'symbol' => $change >= 0 ? '+' : '-',
                    'balance' => number_format($item->new_amount, 2),
                    'date' => $item->created_at->format('M d, Y'),
                    'time' => $item->created_at->format('h:i A'),
                ];
            });

        return Inertia::render('passenger/dashboard/MyWallet', [
            'walletBalance' => number_format($wallet->amount, 2),
            'transactions' => [
                'data' => $transactions->items(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    // This is the infinite scroll endpoint
    public function infiniteTransactions(Request $request)
{
    $user = auth()->user();

    $wallet = EWallet::firstOrCreate(['user_id' => $user->id], ['amount' => 0]);

    $transactions = TransactionHistory::where('e_wallet_id', $wallet->id)
        ->orderBy('created_at', 'desc')
        ->paginate(10)
        ->through(fn($item) => [
            'id' => $item->id,
            'amount' => number_format(abs($item->new_amount - $item->old_amount), 2),
            'symbol' => ($item->new_amount - $item->old_amount) >= 0 ? '+' : '-',
            'balance' => number_format($item->new_amount, 2),
            'date' => $item->created_at->format('M d, Y'),
            'time' => $item->created_at->format('h:i A'),
        ]);

    return response()->json([
        'data' => $transactions->items(),
        'current_page' => $transactions->currentPage(),
        'last_page' => $transactions->lastPage(),
    ]);
}
}
