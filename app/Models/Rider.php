<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'vehicle_plate',
        'current_latitude',
        'current_longitude',
        'status',
    ];

    protected $casts = [
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
