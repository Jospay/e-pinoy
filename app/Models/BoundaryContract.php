<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoundaryContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_id',
        'franchise_id',
        'driver_id',
        'vehicle_id',
        'name',
        'coverage_area',
        'contract_terms',
        'start_date',
        'end_date',
        'renewal_terms',
        'amount',
        'currency',
    ];

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

    // relationship to driver, one to many
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriver::class);
    }

    // relationship to vehicle, one to many
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // relationship to revenue, one to many
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }
}
