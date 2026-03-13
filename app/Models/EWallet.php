<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EWallet extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'hash_seal',
        'last_otp_verified_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function generateSeal(): string
    {
        $formattedAmount = number_format((float)$this->amount, 2, '.', '');
        return hash_hmac('sha256', $this->user_id . $formattedAmount, config('app.key'));
    }

    public function updateAmountAndSeal($newAmount)
    {
        $this->amount = $newAmount;
        $this->hash_seal = $this->generateSeal();
        return $this->save();
    }

    public function isVerified(): bool
    {
        if (!$this->hash_seal) return false;
        return hash_equals($this->hash_seal, $this->generateSeal());
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($wallet) {
            $wallet->hash_seal = $wallet->generateSeal();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TransactionHistory::class);
    }
}
