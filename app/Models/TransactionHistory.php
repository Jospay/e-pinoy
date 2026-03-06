<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionHistory extends Model
{
    protected $table = 'transaction_history';

    protected $fillable = [
        'e_wallet_id',
        'old_amount',
        'new_amount',
        'type',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function eWallet(): BelongsTo
    {
        return $this->belongsTo(EWallet::class);
    }
}
