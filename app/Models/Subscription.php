<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'starts_at',
        'ends_at',
        'status',
        'auto_renew',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    /**
     * Get the user who owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan that the subscription belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Check if the subscription is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'Active' && $this->ends_at->isFuture();
    }
}
