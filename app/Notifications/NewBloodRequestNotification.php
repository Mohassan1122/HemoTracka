<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\BloodRequest;

class NewBloodRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected BloodRequest $bloodRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(BloodRequest $bloodRequest)
    {
        $this->bloodRequest = $bloodRequest;
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
        $name = $notifiable->first_name ?? $notifiable->name ?? 'User';
        $urgency = $this->bloodRequest->urgency_level ?? ($this->bloodRequest->is_emergency ? 'Critical' : 'Standard');

        return (new MailMessage)
            ->subject('New Blood Request - ' . $this->bloodRequest->blood_group)
            ->greeting('Hello ' . $name . ',')
            ->line('A new blood request has been submitted.')
            ->line('**Blood Group:** ' . $this->bloodRequest->blood_group)
            ->line('**Units Needed:** ' . $this->bloodRequest->units_needed)
            ->line('**Urgency:** ' . $urgency)
            ->line('**Needed By:** ' . $this->bloodRequest->needed_by->format('M d, Y H:i'))
            ->action('View Request', url('/api/blood-requests/' . $this->bloodRequest->id))
            ->line('Please review and respond to this request promptly.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_blood_request',
            'blood_request_id' => $this->bloodRequest->id,
            'blood_group' => $this->bloodRequest->blood_group,
            'units_needed' => $this->bloodRequest->units_needed,
            'urgency_level' => $this->bloodRequest->urgency_level,
            'message' => 'New blood request for ' . $this->bloodRequest->units_needed . ' units of ' . $this->bloodRequest->blood_group,
        ];
    }
}
