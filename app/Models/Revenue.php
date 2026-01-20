<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Revenue extends Model
{
    use HasFactory;

    // protected $fillable = [];

    // relationship to status, one to many
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // relationship to franchise, one to many
    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    // relationship to payment option, one to many
    public function paymentOption(): BelongsTo
    {
        return $this->belongsTo(PaymentOption::class);
    }

    // relationship to boundary contract, one to many
    public function boundaryContract(): BelongsTo
    {
        return $this->belongsTo(BoundaryContract::class);
    }

    // relationship to user driver, one to many
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriver::class);
    }

    // relationship to revenue breakdowns, one to many
    public function revenueBreakdowns(): HasMany
    {
        return $this->hasMany(RevenueBreakdown::class);
    }

    // relationship to routes, one to many
    public function route(): HasOne
    {
        // $this->hasOne(RelatedModel::class, 'foreign_key', 'local_key');
        return $this->hasOne(Route::class);
    }
}
