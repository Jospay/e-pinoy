<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Franchise extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'owner_id',
        'manager_id',
        'status_id',
        'email',
        'name',
        'phone',
        'address',
        'region',
        'province',
        'city',
        'barangay',
        'postal_code',
        'latitude',
        'longitude',
        'contract_attachment',
        'dti_registration_attachment',
        'mayor_permit_attachment',
        'proof_agreement_attachment',
    ];

    // relationship to owner, one to many
    public function owner(): BelongsTo
    {
        return $this->belongsTo(UserOwner::class);
    }

    // relationship to manager, one to many
    public function manager(): BelongsTo
    {
        return $this->belongsTo(UserManager::class);
    }

    // relationship to status, one to many
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // relationship to drivers, many to many (pivot table)
    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(UserDriver::class);
    }

    // relationship to technicians, many to many (pivot table)
    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(UserTechnician::class);
    }

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

    // relationship to boundary contracts, one to many
    public function boundaryContracts(): HasMany
    {
        return $this->hasMany(BoundaryContract::class);
    }

    // relationship to vehicles, one to many
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    // relationship to violations, one to many
    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    // relationship to inventories, one to many
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }
}
