<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DonorBadge extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'criteria_type',
        'criteria_value',
        'points',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'points' => 'integer',
        'criteria_value' => 'integer',
    ];

    /**
     * Get the donors who have earned this badge.
     */
    public function donors(): BelongsToMany
    {
        return $this->belongsToMany(Donor::class, 'donor_badge_donor')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    /**
     * Scope for active badges.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if a donor qualifies for this badge.
     */
    public function checkEligibility(Donor $donor): bool
    {
        return match ($this->criteria_type) {
            'donation_count' => $donor->donations()->count() >= $this->criteria_value,
            'units_donated' => $donor->donations()->sum('units') >= $this->criteria_value,
            'first_donation' => $donor->donations()->count() >= 1,
            'blood_type_rare' => in_array($donor->blood_group, ['AB-', 'B-', 'O-']),
            default => false,
        };
    }
}
