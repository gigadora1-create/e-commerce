<?php

namespace App\Http\Controllers;

use App\Notifications\SupplyIssueNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class SupplyNotificationController extends Controller
{
    public function index(Request $request)
    {
        // The bell is an action queue, not a history. Once an alert is read it
        // must leave the list so only pending Proveeduria events remain.
        $notifications = $request->user()
            ->unreadNotifications()
            ->where('type', SupplyIssueNotification::class);

        $unreadCount = (clone $notifications)->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (DatabaseNotification $notification) => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notificacion de Proveeduria',
                    'message' => $notification->data['message'] ?? '',
                    'level' => $notification->data['level'] ?? 'info',
                    'icon' => $notification->data['icon'] ?? 'fa-bell',
                    'url' => $notification->data['url'] ?? route('supplies.issues.index'),
                    'read' => $notification->read_at !== null,
                    'created_at' => $notification->created_at?->diffForHumans(),
                ])
                ->values(),
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification)
    {
        $this->ensureOwnership($request, $notification);
        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->unreadNotifications()
            ->where('type', SupplyIssueNotification::class)
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    private function ensureOwnership(Request $request, DatabaseNotification $notification): void
    {
        abort_unless(
            $notification->notifiable_type === $request->user()::class
                && (int) $notification->notifiable_id === (int) $request->user()->id
                && $notification->type === SupplyIssueNotification::class,
            404
        );
    }
}
