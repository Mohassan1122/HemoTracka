<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRequest extends Model
{
    use HasFactory;

    protected $table = 'users_requests';

    // Status constants
    const STATUS_PENDING = 'Pending';
    const STATUS_RESPONDED = 'Responded';
    const STATUS_FULFILLED = 'Fulfilled';

    protected $fillable = [
        'blood_request_id',
        'user_id',
        'request_source',
        'is_read',
        'status',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Get the blood request associated with this user request.
     */
    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class);
    }

    /**
     * Get the user associated with this request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark this request as read.
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Mark this request as responded (donor scheduled appointment).
     */
    public function markAsResponded(): void
    {
        $this->update(['status' => self::STATUS_RESPONDED]);
    }

    /**
     * Mark this request as fulfilled (donor completed donation).
     */
    public function markAsFulfilled(): void
    {
        $this->update(['status' => self::STATUS_FULFILLED]);
    }

    /**
     * Check if the request has been read.
     */
    public function isRead(): bool
    {
        return (bool) $this->is_read;
    }

    /**
     * Check if the donor has responded to this request.
     */
    public function hasResponded(): bool
    {
        return in_array($this->status, [self::STATUS_RESPONDED, self::STATUS_FULFILLED]);
    }
}
