<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegulatoryBody extends Model
{
    use HasFactory;

    protected $table = 'regulatory_bodies';

    protected $fillable = [
        'user_id',
        'institution_name',
        'license_number',
        'level',
        'state_id',
        'email',
        'phone_number',
        'address',
        'work_days',
        'work_hours',
        'company_website',
        'profile_picture_url',
        'cover_picture_url',
        'notification_preferences',
        'is_active',
    ];

    protected $casts = [
        'notification_preferences' => 'array',
        'is_active' => 'boolean',
        'work_days' => 'array',
        'work_hours' => 'array',
    ];

    /**
     * Get the user associated with the regulatory body.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the state associated with the regulatory body.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the social connections of the regulatory body.
     */
    public function socialConnections(): HasMany
    {
        return $this->hasMany(RegulatoryBodySocialConnection::class);
    }

    /**
     * Get the compliance requests for the regulatory body.
     */
    public function complianceRequests(): HasMany
    {
        return $this->hasMany(ComplianceRequest::class);
    }

    /**
     * Get the compliance monitoring records for the regulatory body.
     */
    public function complianceMonitoring(): HasMany
    {
        return $this->hasMany(ComplianceMonitoring::class);
    }

    /**
     * Check if regulatory body is federal level.
     */
    public function isFederal(): bool
    {
        return $this->level === 'federal';
    }

    /**
     * Check if regulatory body is state level.
     */
    public function isState(): bool
    {
        return $this->level === 'state';
    }

    /**
     * Get all monitored organizations for this regulatory body.
     */
    public function getMonitoredOrganizations()
    {
        if ($this->isFederal()) {
            // Federal regulators see all organizations
            return Organization::all();
        } else {
            // State regulators see only organizations in their state
            return Organization::where('state_id', $this->state_id)->get();
        }
    }
}
