<?php

namespace App\Http\Controllers;

use App\Models\SchoolNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = SchoolNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request, SchoolNotification $schoolNotification): RedirectResponse
    {
        abort_unless($schoolNotification->user_id === $request->user()->id, 403);
        abort_unless($schoolNotification->channel === 'in_app', 403);

        if ($schoolNotification->read_at === null) {
            $schoolNotification->update(['read_at' => now()]);
        }

        return back()->with('status', 'Marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        SchoolNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', 'in_app')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
