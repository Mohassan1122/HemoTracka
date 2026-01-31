<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $delivery;
    public $status;

    /**
     * Create a new event instance.
     */
    public function __construct(Delivery $delivery, string $status)
    {
        $this->delivery = $delivery;
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('delivery.' . $this->delivery->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->delivery->id,
            'status' => $this->status,
            'tracking_code' => $this->delivery->tracking_code,
            'rider' => $this->delivery->rider ? [
                'id' => $this->delivery->rider->id,
                'current_latitude' => $this->delivery->rider->current_latitude,
                'current_longitude' => $this->delivery->rider->current_longitude,
            ] : null,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
