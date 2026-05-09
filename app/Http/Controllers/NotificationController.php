<?php

namespace App\Http\Controllers;

use App\Models\SchoolNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Legacy full-page URL: redirect to dashboard and open the notifications modal.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()
            ->route('dashboard')
            ->with('open_notifications_modal', true);
    }

    public function feed(Request $request): JsonResponse
    {
        $notifications = SchoolNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $unreadCount = SchoolNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications->map(static fn (SchoolNotification $n): array => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ]),
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, SchoolNotification $schoolNotification): JsonResponse|RedirectResponse
    {
        abort_unless($schoolNotification->user_id === $request->user()->id, 403);
        abort_unless($schoolNotification->channel === 'in_app', 403);

        if ($schoolNotification->read_at === null) {
            $schoolNotification->update(['read_at' => now()]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'unread_count' => $this->unreadCountForUser($request),
            ]);
        }

        return back()->with('status', 'Marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        SchoolNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'unread_count' => 0,
            ]);
        }

        return back()->with('status', 'All notifications marked as read.');
    }

    private function unreadCountForUser(Request $request): int
    {
        return (int) SchoolNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->count();
    }
}
