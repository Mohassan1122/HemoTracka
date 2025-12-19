<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'blood_group',
        'type',
        'units_in_stock',
        'threshold',
        'location',
        'expiry_date',
    ];

    protected $casts = [
        'expiry_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function isLowStock(): bool
    {
        return $this->units_in_stock <= $this->threshold;
    }
}
