<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionHistory extends Model
{
    protected $fillable = [
        'e_wallet_id',
        'old_amount',
        'new_amount',
        'type',
    ];

    public function eWallet(): BelongsTo
    {
        return $this->belongsTo(EWallet::class);
    }
}
