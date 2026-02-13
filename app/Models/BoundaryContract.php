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
        'franchise_id',
        'driver_id',
        'vehicle_id',
        'name',
        'coverage_area',
        'contract_terms',
        'start_date',
        'end_date',
        'renewal_terms',
        'currency',
    ];

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

    // relationship to revenue, one to many
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function vehicleTypes()
    {
        return $this->belongsToMany(VehicleType::class)
                    ->withPivot('amount', 'status_id');
    }

    // relationship to branch, one to many
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
