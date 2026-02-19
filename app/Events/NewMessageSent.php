<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Check if recipient wants message notifications
        // We need to fetch the recipient to check preferences
        $recipient = User::find($this->message->to_user_id);

        if ($recipient) {
            $preferences = $recipient->preferences ?? [];
            $notifications = $preferences['notifications'] ?? [];
            $wantsMessages = $notifications['messages'] ?? true;

            if (!$wantsMessages) {
                return []; // Do not broadcast
            }
        }

        // Broadcast to the recipient's private channel
        return [
            new PrivateChannel('messages.' . $this->message->to_user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $sender = User::find($this->message->from_user_id);
        $senderOrg = $sender ? $sender->organization : null;

        return [
            'id' => $this->message->id,
            'from_user_id' => $this->message->from_user_id,
            'to_user_id' => $this->message->to_user_id,
            'body' => $this->message->body,
            'subject' => $this->message->subject,
            'created_at' => $this->message->created_at->toIso8601String(),
            'sender' => $sender ? [
                'id' => $sender->id,
                'first_name' => $sender->first_name,
                'last_name' => $sender->last_name,
                'organization' => $senderOrg ? [
                    'id' => $senderOrg->id,
                    'name' => $senderOrg->name,
                ] : null,
            ] : null,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.new';
    }
}
