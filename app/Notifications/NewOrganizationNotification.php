<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrganizationNotification extends Notification implements ShouldQueue, ShouldBroadcast
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
        return ['mail', 'database', 'broadcast'];
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
            'type' => 'new_organization',
            'title' => 'New Organization Registration',
            'organization_id' => $this->organization->id,
            'name' => $this->organization->name,
            'org_type' => $this->organization->type,
            'message' => "New {$this->organization->type} registered: {$this->organization->name}",
            'action_url' => "/admin/organizations/{$this->organization->id}",
        ];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'organization.new';
    }
}
