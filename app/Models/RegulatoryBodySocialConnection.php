<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegulatoryBodySocialConnection extends Model
{
    use HasFactory;

    protected $table = 'regulatory_body_social_connections';

    protected $fillable = [
        'regulatory_body_id',
        'platform',
        'handle',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * Get the regulatory body associated with this social connection.
     */
    public function regulatoryBody(): BelongsTo
    {
        return $this->belongsTo(RegulatoryBody::class);
    }
}
