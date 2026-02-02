<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $appointment;
    protected $action; // 'confirmed', 'cancelled', 'updated'
    protected $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment, string $action, ?string $message = null)
    {
        $this->appointment = $appointment;
        $this->action = $action;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $organizationName = $this->appointment->organization->name ?? 'Blood Bank';

        return (new MailMessage)
            ->subject($this->getSubject())
            ->greeting("Hello {$notifiable->first_name},")
            ->line($this->getMessage())
            ->line("**Appointment Details:**")
            ->line("Date: " . $this->appointment->appointment_date->format('l, F j, Y'))
            ->line("Time: " . $this->appointment->appointment_time)
            ->line("Location: {$organizationName}")
            ->line("Donation Type: " . $this->appointment->donation_type)
            ->when($this->action === 'cancelled' && $this->appointment->cancellation_reason, function ($mail) {
                return $mail->line("**Reason:** " . $this->appointment->cancellation_reason);
            })
            ->action('View Appointments', url('/donor/appointments'))
            ->line('Thank you for your commitment to saving lives!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        return $this->getData();
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->getData());
    }

    /**
     * Get the notification data.
     */
    protected function getData(): array
    {
        return [
            'type' => 'appointment_update',
            'title' => $this->getSubject(),
            'appointment_id' => $this->appointment->id,
            'action' => $this->action,
            'message' => $this->getMessage(),
            'appointment_date' => $this->appointment->appointment_date,
            'appointment_time' => $this->appointment->appointment_time,
            'organization_name' => $this->appointment->organization->name ?? 'Blood Bank',
            'donation_type' => $this->appointment->donation_type,
            'cancellation_reason' => $this->appointment->cancellation_reason,
        ];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'appointment.' . $this->action;
    }

    /**
     * Get the notification subject.
     */
    protected function getSubject(): string
    {
        return match ($this->action) {
            'confirmed' => 'Appointment Confirmed',
            'cancelled' => 'Appointment Cancelled',
            'updated' => 'Appointment Updated',
            default => 'Appointment Notification',
        };
    }

    /**
     * Get the notification message.
     */
    protected function getMessage(): string
    {
        if ($this->message) {
            return $this->message;
        }

        return match ($this->action) {
            'confirmed' => 'Your blood donation appointment has been confirmed!',
            'cancelled' => 'Your blood donation appointment has been cancelled.',
            'updated' => 'Your blood donation appointment has been updated.',
            default => 'Your appointment status has changed.',
        };
    }
}
