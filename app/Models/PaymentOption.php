<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentOption extends Model
{
    use HasFactory;

    // protected $fillable = [];

    // relationship to expenses, one to many
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // relationship to revenues, one to many
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }
}
