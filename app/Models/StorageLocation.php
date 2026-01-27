<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorageLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'location_type',
        'parent_location_id',
        'capacity',
        'current_load',
        'min_temperature',
        'max_temperature',
        'current_temperature',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_temperature' => 'decimal:2',
        'max_temperature' => 'decimal:2',
        'current_temperature' => 'decimal:2',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parentLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'parent_location_id');
    }

    public function subLocations(): HasMany
    {
        return $this->hasMany(StorageLocation::class, 'parent_location_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'storage_location_id');
    }

    // Helpers
    public function getFullPathAttribute()
    {
        if ($this->parentLocation) {
            return $this->parentLocation->full_path . ' > ' . $this->name;
        }
        return $this->name;
    }

    public function isCapacityFull(): bool
    {
        if (is_null($this->capacity))
            return false;
        return $this->current_load >= $this->capacity;
    }
}
