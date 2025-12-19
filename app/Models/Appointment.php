<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'donor_id',
        'organization_id',
        'appointment_date',
        'appointment_time',
        'status',
        'donation_type',
        'notes',
        'cancellation_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime:H:i',
    ];

    /**
     * Get the donor that owns the appointment.
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    /**
     * Get the organization (blood bank) for the appointment.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Check if the appointment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['Scheduled', 'Confirmed']);
    }

    /**
     * Check if the appointment is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->appointment_date >= now()->startOfDay()
            && in_array($this->status, ['Scheduled', 'Confirmed']);
    }

    /**
     * Scope for upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->startOfDay())
            ->whereIn('status', ['Scheduled', 'Confirmed'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time');
    }

    /**
     * Scope for past appointments.
     */
    public function scopePast($query)
    {
        return $query->where('appointment_date', '<', now()->startOfDay())
            ->orWhereIn('status', ['Completed', 'Cancelled', 'No-Show']);
    }
}
