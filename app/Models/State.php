<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'region',
    ];

    /**
     * Get the regulatory bodies for this state.
     */
    public function regulatoryBodies(): HasMany
    {
        return $this->hasMany(RegulatoryBody::class);
    }
}
