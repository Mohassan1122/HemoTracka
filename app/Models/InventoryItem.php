<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'storage_location_id',
        // Quality control
        'quality_status',
        'quality_checked_at',
        'quality_checked_by',
        'quality_notes',
        // Donor traceability
        'donor_id',
        'donation_id',
        // Component separation
        'parent_item_id',
        'is_component',
        'component_type',
        'separated_at',
        // Temperature
        'last_recorded_temp',
        'temp_recorded_at',
        'temp_breach',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'is_component' => 'boolean',
        'temp_breach' => 'boolean',
        'quality_checked_at' => 'datetime',
        'separated_at' => 'datetime',
        'temp_recorded_at' => 'datetime',
        'last_recorded_temp' => 'decimal:2',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function qualityCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_checked_by');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class);
    }

    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'parent_item_id');
    }

    public function childComponents(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'parent_item_id');
    }

    // Helpers
    public function isLowStock(): bool
    {
        return $this->units_in_stock <= $this->threshold;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isQualityApproved(): bool
    {
        return $this->quality_status === 'passed';
    }

    public function hasTemperatureBreach(): bool
    {
        return $this->temp_breach === true;
    }
}
