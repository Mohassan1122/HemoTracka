<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = [
        'donor_id',
        'organization_id',
        'blood_group',
        'units',
        'platelets_type',
        'donation_date',
        'notes',
        'doctor_notes',
        'status',
    ];

    protected $casts = [
        'donation_date' => 'date',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(Donor::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
