<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationStatusNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $organization;
    protected $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
        $this->status = $organization->status;
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
        $message = (new MailMessage)
            ->subject("Account Status Update: {$this->status}")
            ->greeting("Hello,");

        if ($this->status === 'Active') {
            $message->line("We are pleased to inform you that your organization \"{$this->organization->name}\" has been approved and is now Active on the HemoTracka platform.")
                ->action('Go to Dashboard', url('/dashboard'));
        } elseif ($this->status === 'Suspended') {
            $message->line("Your organization \"{$this->organization->name}\" has been suspended. Please contact support for more details.")
                ->error();
        } else {
            $message->line("Your organization status has been updated to: {$this->status}.");
        }

        return $message->line('Thank you for using HemoTracka!');
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
            'type' => 'organization_status',
            'title' => 'Account Status Updated',
            'organization_id' => $this->organization->id,
            'status' => $this->status,
            'message' => "Your organization status has been updated to {$this->status}",
            'action_url' => '/settings',
        ];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'organization.status';
    }
}
