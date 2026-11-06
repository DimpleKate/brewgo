<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return $this->renderIndex($request, 'notifications.index');
    }

    public function adminIndex(Request $request): View
    {
        return $this->renderIndex($request, 'admin.notifications.index');
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $notification->markAsRead();

        return redirect($this->notificationUrl($notification));
    }

    private function renderIndex(Request $request, string $view): View
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(15);

        return view($view, compact('notifications'));
    }

    private function notificationUrl(DatabaseNotification $notification): string
    {
        $url = $notification->data['url'] ?? null;

        if (! is_string($url) || $url === '') {
            return request()->is('admin/*') ? route('admin.notifications.index') : route('notifications.index');
        }

        return $url;
    }
}
