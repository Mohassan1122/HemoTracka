<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Organization extends Model implements Authenticatable
{
    use HasFactory, SoftDeletes, AuthenticatableTrait, HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'type',
        'role',
        'license_number',
        'address',
        'contact_email',
        'email', // Auth email
        'password',
        'phone',
        'logo',
        'cover_photo',
        'latitude',
        'longitude',
        'operating_hours',
        'description',
        'services',
        'facebook_link',
        'twitter_link',
        'instagram_link',
        'linkedin_link',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'type' => 'string',
        'status' => 'string',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'operating_hours' => 'array',
        'services' => 'array',
        'password' => 'hashed',
    ];

    protected $appends = [
        'logo_url',
        'cover_photo_url',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function donors(): HasMany
    {
        return $this->hasMany(Donor::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function bloodRequests(): HasMany
    {
        return $this->hasMany(BloodRequest::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function feedback()
    {
        return $this->morphMany(Feedback::class, 'target');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * Get the logo URL for API responses.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    /**
     * Get the cover photo URL for API responses.
     */
    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo ? asset('storage/' . $this->cover_photo) : null;
    }
}
