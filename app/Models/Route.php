<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasFactory;

    // protected $fillable = [];

    // relationship to status, one to many
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // relationship to driver, one to many
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriver::class);
    }

    // relationship to passenger, one to many
    public function passenger(): BelongsTo
    {
        return $this->belongsTo(UserPassenger::class);
    }

    // relationship to vehicle, one to many
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // relationship to revenue, one to many
    public function revenue(): BelongsTo
    {
        return $this->belongsTo(Revenue::class);
    }

    // relationship to ratings, one to many
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
}
