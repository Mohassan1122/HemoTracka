<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationRequest extends Model
{
    use HasFactory;

    protected $table = 'organization_requests';

    protected $fillable = [
        'blood_request_id',
        'organization_id',
        'request_source',
        'status',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Get the blood request associated with this organization request.
     */
    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class);
    }

    /**
     * Get the organization associated with this request.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Mark this request as read.
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Check if the request has been read.
     */
    public function isRead(): bool
    {
        return (bool) $this->is_read;
    }
}
