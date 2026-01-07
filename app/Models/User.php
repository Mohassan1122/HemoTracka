<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    // Role constants (application-level enforcement)
    public const ROLE_ADMIN = 'admin';
    public const ROLE_DONOR = 'donor';
    public const ROLE_RIDER = 'rider';
    public const ROLE_FACILITIES = 'facilities';
    public const ROLE_BLOOD_BANKS = 'blood_banks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'date_of_birth',
        'gender',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'password' => 'hashed',
        'profile_picture' => 'string',
    ];

    protected $appends = [
        'profile_picture_url',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function donor(): HasOne
    {
        return $this->hasOne(Donor::class);
    }

    public function rider(): HasOne
    {
        return $this->hasOne(Rider::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'from_user_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'to_user_id');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getProfilePictureUrlAttribute(): ?string
    {
        return $this->profile_picture ? asset('storage/' . $this->profile_picture) : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDonor(): bool
    {
        return $this->role === 'donor';
    }

    public function isRider(): bool
    {
        return $this->role === 'rider';
    }

    public function isFacility(): bool
    {
        return $this->role === 'facilities';
    }

    public function isBloodBank(): bool
    {
        return $this->role === 'blood_banks';
    }

    /**
     * Get the list of valid user roles (value => label).
     */
    public static function getValidRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_DONOR => 'Blood Donor',
            self::ROLE_RIDER => 'Delivery Rider',
            self::ROLE_FACILITIES => 'Facilities (Hospitals/Regulatory Bodies)',
            self::ROLE_BLOOD_BANKS => 'Blood Banks',
        ];
    }

    /**
     * Get only the role values (for Rule::in)
     */
    public static function validRoleValues(): array
    {
        return array_keys(self::getValidRoles());
    }
}
