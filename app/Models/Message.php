<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'subject',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }
}
