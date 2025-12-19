<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrganizationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $organization;

    /**
     * Create a new notification instance.
     */
    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
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
            ->subject('New Organization Registration Pending')
            ->greeting('Hello Admin,')
            ->line("A new {$this->organization->type} named \"{$this->organization->name}\" has registered and is awaiting approval.")
            ->line("License Number: {$this->organization->license_number}")
            ->action('Review Organization', url('/admin/organizations'))
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
            'organization_id' => $this->organization->id,
            'name' => $this->organization->name,
            'type' => $this->organization->type,
            'message' => "New {$this->organization->type} registered: {$this->organization->name}",
            'action_url' => "/admin/organizations/{$this->organization->id}",
        ];
    }
}
