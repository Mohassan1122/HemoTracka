<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Donation;

class DonationRecordedNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected Donation $donation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
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
            ->subject('Thank You for Your Donation!')
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('Thank you for your life-saving blood donation!')
            ->line('**Donation Date:** ' . $this->donation->donation_date->format('M d, Y'))
            ->line('**Blood Group:** ' . $this->donation->blood_group)
            ->line('**Units Donated:** ' . $this->donation->units)
            ->line('Your donation could save up to 3 lives!')
            ->line('Remember to stay hydrated and rest after your donation.')
            ->line('Thank you for being a hero!');
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
            'type' => 'donation_recorded',
            'title' => 'Donation Recorded',
            'donation_id' => $this->donation->id,
            'donation_date' => $this->donation->donation_date->toDateString(),
            'blood_group' => $this->donation->blood_group,
            'units' => $this->donation->units,
            'message' => 'Thank you for donating ' . $this->donation->units . ' unit(s) of ' . $this->donation->blood_group . ' blood!',
        ];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'donation.recorded';
    }
}
