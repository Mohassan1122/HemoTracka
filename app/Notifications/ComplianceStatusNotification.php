<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use App\Models\ComplianceRequest;

class ComplianceStatusNotification extends Notification
{
    use Queueable;

    protected ComplianceRequest $complianceRequest;
    protected string $status;
    protected ?string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(ComplianceRequest $complianceRequest, string $status, ?string $reason = null)
    {
        $this->complianceRequest = $complianceRequest;
        $this->status = $status;
        $this->reason = $reason;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification (for database).
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
        $message = $this->status === 'approved'
            ? "Your compliance request has been approved."
            : "Your compliance request has been rejected.";

        return [
            'type' => 'compliance_status',
            'title' => $this->status === 'approved' ? 'Compliance Request Approved' : 'Compliance Request Rejected',
            'message' => $message,
            'compliance_request_id' => $this->complianceRequest->id,
            'status' => $this->status,
            'reason' => $this->reason,
            'organization_name' => $this->complianceRequest->organization?->name,
            'reviewed_at' => now()->toISOString(),
        ];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'compliance.status';
    }
}
