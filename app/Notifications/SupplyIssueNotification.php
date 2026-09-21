<?php

namespace App\Notifications;

use App\Models\SupplyIssueRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupplyIssueNotification extends Notification
{
    use Queueable;

    public const EVENT_NEW_REQUEST = 'new_request';
    public const EVENT_READY = 'ready';
    public const EVENT_REJECTED = 'rejected';
    public const EVENT_PENDING_SUPPORT = 'pending_support';
    public const EVENT_CLOSED = 'closed';

    public function __construct(
        private readonly SupplyIssueRequest $issueRequest,
        private readonly string $event
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $requester = $this->issueRequest->requestedBy?->name ?? 'un usuario';

        [$title, $message, $level, $icon] = match ($this->event) {
            self::EVENT_NEW_REQUEST => [
                'Nueva solicitud para alistar',
                "{$this->issueRequest->request_number} fue enviada por {$requester} y requiere alistamiento.",
                'warning',
                'fa-clipboard-list',
            ],
            self::EVENT_READY => [
                'Solicitud lista para recoger',
                "Tu solicitud {$this->issueRequest->request_number} ya esta lista en Proveeduria.",
                'success',
                'fa-box-open',
            ],
            self::EVENT_REJECTED => [
                'Solicitud rechazada',
                "La solicitud {$this->issueRequest->request_number} fue rechazada. Revisa las observaciones de Proveeduria.",
                'danger',
                'fa-times-circle',
            ],
            self::EVENT_PENDING_SUPPORT => [
                'Entrega pendiente de soporte',
                "La entrega de {$this->issueRequest->request_number} fue registrada y queda pendiente el soporte firmado.",
                'info',
                'fa-file-signature',
            ],
            default => [
                'Solicitud cerrada',
                "La solicitud {$this->issueRequest->request_number} fue cerrada correctamente.",
                'success',
                'fa-check-circle',
            ],
        };

        return [
            'module' => 'supplies',
            'event' => $this->event,
            'issue_request_id' => $this->issueRequest->id,
            'request_number' => $this->issueRequest->request_number,
            'title' => $title,
            'message' => $message,
            'level' => $level,
            'icon' => $icon,
            // A relative URL keeps the notification valid across localhost,
            // production domains, and deployments on non-standard ports.
            'url' => route('supplies.issues.show', $this->issueRequest, false),
        ];
    }
}
