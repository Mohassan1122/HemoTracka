<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\InventoryItem;

class LowStockAlertNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
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
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock_alert',
            'inventory_item_id' => $this->item->id,
            'blood_group' => $this->item->blood_group,
            'type' => $this->item->type,
            'units_in_stock' => $this->item->units_in_stock,
            'threshold' => $this->item->threshold,
            'message' => 'Low stock alert for ' . $this->item->blood_group . ' (' . $this->item->type . '): ' . $this->item->units_in_stock . ' units remaining',
        ];
    }
}
