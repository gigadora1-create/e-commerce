<?php

namespace App\Services\Supply;

use App\Models\SupplyIssueRequest;
use App\Models\User;
use App\Notifications\SupplyIssueNotification;
use Spatie\Permission\Models\Role;

class SupplyIssueNotificationService
{
    public function notifyAdminsOfNewRequest(SupplyIssueRequest $issueRequest): void
    {
        $this->loadRequestContext($issueRequest);

        // Superadministrators supervise every module and are the fallback when
        // no operational Proveeduria administrator has been assigned yet.
        $recipientRoles = Role::query()
            ->whereIn('name', ['PROVEEDURIA_ADMIN', 'SUPERADMIN', 'SUPER_ADMIN'])
            ->pluck('name')
            ->all();

        if ($recipientRoles === []) {
            return;
        }

        User::role($recipientRoles)
            ->where('is_active', true)
            ->each(fn (User $user) => $user->notify(
                new SupplyIssueNotification($issueRequest, SupplyIssueNotification::EVENT_NEW_REQUEST)
            ));
    }

    public function notifyRequester(SupplyIssueRequest $issueRequest, string $event): void
    {
        $this->loadRequestContext($issueRequest);

        $issueRequest->requestedBy?->notify(new SupplyIssueNotification($issueRequest, $event));
    }

    public function markIssueNotificationsAsRead(User $user, SupplyIssueRequest $issueRequest): void
    {
        $user->unreadNotifications()
            ->where('type', SupplyIssueNotification::class)
            ->cursor()
            ->filter(fn ($notification) => (int) ($notification->data['issue_request_id'] ?? 0) === (int) $issueRequest->id)
            ->each->markAsRead();
    }

    private function loadRequestContext(SupplyIssueRequest $issueRequest): void
    {
        $issueRequest->loadMissing('requestedBy:id,name');
    }
}
