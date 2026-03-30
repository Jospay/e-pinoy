<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'franchise_id',
        'branch_id',
        'maintenance_id',
        'invoice_no',
        'amount',
        'currency',
        'notes',
        'payment_date',
    ];

    // relationship to franchise
    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // relationship to maintenance
    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }
}
