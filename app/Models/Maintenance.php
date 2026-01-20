<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'status_id',
        'technician_id',
        'franchise_id',
        'inventory_id',
        'description',
        'maintenance_date',
        'next_maintenance_date',
    ];

    // relationship to status, one to many
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // relationship to vehicle, one to many
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    // relationship to technician, one to many
    public function technician(): BelongsTo
    {
        return $this->belongsTo(UserTechnician::class);
    }

    // relationship to inventory, one to many
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    // relationship to expenses, one to many
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
