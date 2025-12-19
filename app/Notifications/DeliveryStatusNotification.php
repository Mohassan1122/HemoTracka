<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Delivery;

class DeliveryStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Delivery $delivery;
    protected string $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(Delivery $delivery, string $status)
    {
        $this->delivery = $delivery;
        $this->status = $status;
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
        $message = (new MailMessage)
            ->subject('Delivery Update - ' . $this->delivery->tracking_code)
            ->greeting('Hello ' . $notifiable->first_name . ',');

        switch ($this->status) {
            case 'Assigned':
                $message->line('A rider has been assigned to your delivery.');
                break;
            case 'Picked Up':
                $message->line('Your blood delivery has been picked up and is on its way.');
                break;
            case 'In Transit':
                $message->line('Your delivery is currently in transit.');
                break;
            case 'Delivered':
                $message->line('Your blood delivery has been completed successfully!');
                break;
            default:
                $message->line('Your delivery status has been updated to: ' . $this->status);
        }

        return $message
            ->line('**Tracking Code:** ' . $this->delivery->tracking_code)
            ->action('Track Delivery', url('/api/deliveries/track/' . $this->delivery->tracking_code))
            ->line('Thank you for using HemoTracka!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'delivery_status_update',
            'delivery_id' => $this->delivery->id,
            'tracking_code' => $this->delivery->tracking_code,
            'status' => $this->status,
            'message' => 'Delivery ' . $this->delivery->tracking_code . ' status updated to ' . $this->status,
        ];
    }
}
