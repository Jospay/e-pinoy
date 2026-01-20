<?php

namespace App\Models;

use Database\Seeders\UserTypeSeeder;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_type_id',
        'username',
        'name',
        'email',
        'phone',
        'gender',
        'address',
        'region',
        'province',
        'city',
        'barangay',
        'postal_code',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the options for generating the slug.
     */
    // public function getSlugOptions() : SlugOptions
    // {
    //     return SlugOptions::create()
    //         ->generateSlugsFrom('name')
    //         ->saveSlugsTo('username')
    //         ->slugsShouldBeNoLongerThan(20)
    //         ->usingSeparator('_');
    // }

    // relationship to user type, one to many
    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    // relationship to driver, one to one
    public function driverDetails(): HasOne
    {
        return $this->hasOne(UserDriver::class, 'id');
    }

    // relationship to technician, one to one
    public function technicianDetails(): HasOne
    {
        return $this->hasOne(UserTechnician::class, 'id');
    }

    // relationship to passenger, one to one
    public function passengerDetails(): HasOne
    {
        return $this->hasOne(UserPassenger::class, 'id');
    }

    // relationship to owner, one to one
    public function ownerDetails(): HasOne
    {
        return $this->hasOne(UserOwner::class, 'id');
    }

    // relationship to manager, one to one
    public function managerDetails(): HasOne
    {
        return $this->hasOne(UserManager::class, 'id');
    }

    public function getStatusName(): ?string
    {
        return $this->driverDetails?->status->name
            ?? $this->technicianDetails?->status->name
            ?? $this->managerDetails?->status->name
            ?? $this->ownerDetails?->status->name
            ?? $this->passengerDetails?->status->name
            ?? null;
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }
}
