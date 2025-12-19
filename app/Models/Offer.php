<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'blood_request_id',
        'organization_id',
        'product_fee',
        'shipping_fee',
        'card_charge',
        'total_amount',
        'status',
        'notes',
    ];

    /**
     * Get the blood request that this offer belongs to.
     */
    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class);
    }

    /**
     * Get the organization (Blood Bank) that made this offer.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
