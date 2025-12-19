<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'blood_request_id',
        'rider_id',
        'pickup_location',
        'dropoff_location',
        'pickup_time',
        'delivery_time',
        'status',
        'tracking_code',
        'status_history',
        'receiver_confirmed_at',
    ];

    protected $casts = [
        'pickup_time' => 'datetime',
        'delivery_time' => 'datetime',
        'status_history' => 'array',
        'receiver_confirmed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($delivery) {
            if (empty($delivery->tracking_code)) {
                $delivery->tracking_code = 'HT-' . strtoupper(Str::random(8));
            }
        });
    }

    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }
}
