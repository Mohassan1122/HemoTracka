<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Donor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'organization_id',
        'first_name',
        'last_name',
        'other_names',
        'blood_group',
        'genotype',
        'height',
        'date_of_birth',
        'last_donation_date',
        'address',
        'phone',
        'notes',
        'status',
        'instagram_handle',
        'twitter_handle',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_donation_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(DonorBadge::class, 'donor_badge_donor')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Check if donor is eligible to donate (56 days between whole blood donations).
     */
    public function isEligibleToDonate(): bool
    {
        if ($this->status !== 'Eligible') {
            return false;
        }

        if (!$this->last_donation_date) {
            return true;
        }

        return $this->last_donation_date->addDays(56)->isPast();
    }

    /**
     * Get the next eligible donation date.
     */
    public function getNextEligibleDateAttribute(): ?Carbon
    {
        if (!$this->last_donation_date) {
            return now();
        }

        return $this->last_donation_date->addDays(56);
    }

    /**
     * Get total units donated.
     */
    public function getTotalUnitsDonatedAttribute(): int
    {
        return $this->donations()->sum('units');
    }

    /**
     * Get total reward points.
     */
    public function getTotalPointsAttribute(): int
    {
        return $this->badges()->sum('points');
    }
}
