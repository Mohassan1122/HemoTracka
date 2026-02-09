<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceRequest extends Model
{
    use HasFactory;

    protected $table = 'compliance_requests';

    protected $fillable = [
        'regulatory_body_id',
        'organization_id',
        'organization_type',
        'request_type',
        'description',
        'priority',
        'status',
        'submission_date',
        'required_documents',
        'approved_at',
        'rejection_reason',
        'reviewed_by_id',
        'notes',
    ];

    protected $casts = [
        'submission_date' => 'datetime',
        'approved_at' => 'datetime',
        'required_documents' => 'array',
    ];

    /**
     * Get the regulatory body associated with this compliance request.
     */
    public function regulatoryBody(): BelongsTo
    {
        return $this->belongsTo(RegulatoryBody::class);
    }

    /**
     * Get the organization associated with this compliance request.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who reviewed this compliance request.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'Rejected';
    }

    /**
     * Mark the request as approved.
     */
    public function approve(int $reviewedById, ?string $notes = null): void
    {
        $this->update([
            'status' => 'Approved',
            'approved_at' => now(),
            'reviewed_by_id' => $reviewedById,
            'notes' => $notes,
        ]);
    }

    /**
     * Mark the request as rejected.
     */
    public function reject(int $reviewedById, string $rejectionReason, ?string $notes = null): void
    {
        $this->update([
            'status' => 'Rejected',
            'reviewed_by_id' => $reviewedById,
            'rejection_reason' => $rejectionReason,
            'notes' => $notes,
        ]);
    }
}
