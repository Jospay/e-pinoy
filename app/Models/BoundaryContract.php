<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BoundaryContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_id',
        'branch_id',
        'driver_id',
        'vehicle_id',
        'name',
        'coverage_area',
        'contract_terms',
        'start_date',
        'end_date',
        'renewal_terms',
        'currency',
        'tricycle_terminal_id',
    ];

    /**
     * Relationship to vehicle
     * This fixes the RelationNotFoundException
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Relationship to franchise
    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    // Relationship to driver
    public function driver(): BelongsTo
    {
        return $this->belongsTo(UserDriver::class, 'driver_id');
    }

    // Relationship to revenue
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    public function vehicleTypes(): BelongsToMany
    {
        return $this->belongsToMany(VehicleType::class)
                    ->withPivot('amount', 'status_id');
    }

    // Relationship to branch
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function tricycleTerminal(): BelongsTo
    {
        return $this->belongsTo(
            TricycleTerminal::class,
            'tricycle_terminal_id'
        );
    }
}
