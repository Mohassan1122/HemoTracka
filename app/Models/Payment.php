<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'blood_request_id',
        'amount',
        'payment_method',
        'status',
        'transaction_reference',
        'payment_details',
    ];

    protected $casts = [
        'payment_details' => 'array',
    ];

    /**
     * Get the user who made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the blood request associated with the payment.
     */
    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class);
    }
}
