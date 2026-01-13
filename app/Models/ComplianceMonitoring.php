<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceMonitoring extends Model
{
    use HasFactory;

    protected $table = 'compliance_monitoring';

    protected $fillable = [
        'regulatory_body_id',
        'organization_id',
        'inspection_id',
        'facility_type',
        'compliance_status',
        'last_inspection_date',
        'next_inspection_date',
        'violations_found',
        'notes',
    ];

    protected $casts = [
        'last_inspection_date' => 'datetime',
        'next_inspection_date' => 'datetime',
    ];

    /**
     * Get the regulatory body associated with this monitoring record.
     */
    public function regulatoryBody(): BelongsTo
    {
        return $this->belongsTo(RegulatoryBody::class);
    }

    /**
     * Get the organization being monitored.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if the organization is compliant (score >= 80).
     */
    public function isCompliant(): bool
    {
        return $this->compliance_score >= 80;
    }

    /**
     * Check if the organization is at risk (score < 80 and >= 60).
     */
    public function isAtRisk(): bool
    {
        return $this->compliance_score < 80 && $this->compliance_score >= 60;
    }

    /**
     * Check if the organization is non-compliant (score < 60).
     */
    public function isNonCompliant(): bool
    {
        return $this->compliance_score < 60;
    }

    /**
     * Get compliance status label.
     */
    public function getStatusLabel(): string
    {
        if ($this->isCompliant()) {
            return 'Compliant';
        } elseif ($this->isAtRisk()) {
            return 'At Risk';
        } else {
            return 'Non-Compliant';
        }
    }
}
