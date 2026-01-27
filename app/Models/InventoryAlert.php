<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'inventory_item_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'is_read',
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'acknowledgment_notes',
        'email_sent',
        'email_sent_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_acknowledged' => 'boolean',
        'email_sent' => 'boolean',
        'acknowledged_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    // Helper methods
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    public function acknowledge($userId, $notes = null): void
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'acknowledgment_notes' => $notes,
        ]);
    }

    public function markEmailSent(): void
    {
        $this->update([
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);
    }
}
