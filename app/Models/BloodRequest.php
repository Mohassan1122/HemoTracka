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
        'genotype',
        'units_needed',
        'min_units_bank_can_send',
        'source_type',
        'request_source',
        'type',
        'bone_marrow_type',
        'platelets_type',
        'is_emergency',
        'needed_by',
        'status',
        'product_fee',
        'shipping_fee',
        'card_charge',
        'total_amount',
        'notes',
        'view_count',
    ];

    protected $casts = [
        'needed_by' => 'datetime',
        'view_count' => 'integer',
    ];

    /**
     * Increment the view count for this blood request.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

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

    public function userRequests(): HasMany
    {
        return $this->hasMany(UserRequest::class);
    }

    public function organizationRequests(): HasMany
    {
        return $this->hasMany(OrganizationRequest::class);
    }
}
