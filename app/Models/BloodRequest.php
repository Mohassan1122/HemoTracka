<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'blood_group',
        'units_needed',
        'patient_name',
        'hospital_unit',
        'source_type',
        'type',
        'bone_marrow_type',
        'platelets_type',
        'urgency_level',
        'needed_by',
        'status',
        'product_fee',
        'shipping_fee',
        'card_charge',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'needed_by' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
