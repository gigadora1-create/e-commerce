<?php

namespace App\Policies;

use App\Models\SupplyIssueRequest;
use App\Models\SupplyRequest;
use App\Models\User;

class SupplyPolicy
{
    public function viewAdminDashboard(User $user): bool
    {
        return $user->can('supplies.admin');
    }

    public function manageProducts(User $user): bool
    {
        return $user->can('supplies.admin');
    }

    public function manageClients(User $user): bool
    {
        return $user->can('supplies.admin');
    }

    public function createRequest(User $user): bool
    {
        return $user->can('supplies.admin');
    }

    public function auditRequest(User $user, SupplyRequest $supplyRequest): bool
    {
        if (!$user->can('supplies.admin')) {
            return false;
        }

        return $supplyRequest->status === SupplyRequest::STATUS_REQUESTED;
    }

    public function viewRequest(User $user, SupplyRequest $supplyRequest): bool
    {
        if ($user->can('supplies.admin')) {
            return true;
        }

        return (int) $supplyRequest->requested_by_user_id === (int) $user->id;
    }

    public function viewIssueDashboard(User $user): bool
    {
        return $user->can('supplies.request') || $user->can('supplies.admin');
    }

    public function createIssueRequest(User $user): bool
    {
        return $user->can('supplies.request');
    }

    public function viewIssueRequest(User $user, SupplyIssueRequest $issueRequest): bool
    {
        if ($user->can('supplies.admin')) {
            return true;
        }

        return (int) $issueRequest->requested_by_user_id === (int) $user->id;
    }

    public function markReady(User $user, SupplyIssueRequest $issueRequest): bool
    {
        if (!$user->can('supplies.admin')) {
            return false;
        }

        return $issueRequest->status === SupplyIssueRequest::STATUS_PREPARING;
    }

    public function close(User $user, SupplyIssueRequest $issueRequest): bool
    {
        if (!$user->can('supplies.admin')) {
            return false;
        }

        return !in_array($issueRequest->status, [
            SupplyIssueRequest::STATUS_CLOSED,
            SupplyIssueRequest::STATUS_REJECTED,
        ], true);
    }

    public function reject(User $user, SupplyIssueRequest $issueRequest): bool
    {
        if (!$user->can('supplies.admin')) {
            return false;
        }

        return !in_array($issueRequest->status, [
            SupplyIssueRequest::STATUS_CLOSED,
            SupplyIssueRequest::STATUS_REJECTED,
        ], true);
    }

    public function viewIssuePdf(User $user, SupplyIssueRequest $issueRequest): bool
    {
        if ($user->can('supplies.admin')) {
            return true;
        }

        if ((int) $issueRequest->requested_by_user_id !== (int) $user->id) {
            return false;
        }

        return $issueRequest->status === SupplyIssueRequest::STATUS_CLOSED;
    }
}
