<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\InventoryItem;

class LowStockAlertNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected InventoryItem $item;

    /**
     * Create a new notification instance.
     */
    public function __construct(InventoryItem $item)
    {
        $this->item = $item;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Low Stock Alert - ' . $this->item->blood_group)
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('Your inventory is running low!')
            ->line('**Blood Group:** ' . $this->item->blood_group)
            ->line('**Type:** ' . $this->item->type)
            ->line('**Current Stock:** ' . $this->item->units_in_stock . ' units')
            ->line('**Threshold:** ' . $this->item->threshold . ' units')
            ->action('View Inventory', url('/api/inventory/' . $this->item->id))
            ->line('Please restock as soon as possible.');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->getData();
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->getData());
    }

    /**
     * Get the notification data.
     */
    protected function getData(): array
    {
        return [
            'type' => 'low_stock_alert',
            'title' => '⚠️ Low Stock Alert',
            'inventory_item_id' => $this->item->id,
            'blood_group' => $this->item->blood_group,
            'item_type' => $this->item->type,
            'units_in_stock' => $this->item->units_in_stock,
            'threshold' => $this->item->threshold,
            'message' => 'Low stock alert for ' . $this->item->blood_group . ' (' . $this->item->type . '): ' . $this->item->units_in_stock . ' units remaining',
        ];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'inventory.low_stock';
    }
}
