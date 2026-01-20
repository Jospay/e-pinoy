<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueBreakdown extends Model
{
    use HasFactory;

    // protected $fillable = [];

    // relationship to revenue, one to many
    public function revenue(): BelongsTo
    {
        return $this->belongsTo(Revenue::class);
    }

    // relationship to percentage type, one to many
    public function percentageType(): BelongsTo
    {
        return $this->belongsTo(PercentageType::class);
    }

}
