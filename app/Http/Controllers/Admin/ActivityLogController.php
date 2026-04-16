<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\UserSession;
use App\Services\SessionTracker;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    private SessionTracker $sessionTracker;

    public function __construct(SessionTracker $sessionTracker)
    {
        $this->sessionTracker = $sessionTracker;
    }

    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        if (! auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->input('action') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', '%' . $request->input('ip_address') . '%');
        }

        $logs = $query->paginate(10);
        $actions = ActivityLog::distinct()->pluck('action');

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'actions' => $actions,
            'filters' => $request->only(['user_id', 'action', 'status', 'date_from', 'date_to', 'ip_address']),
        ]);
    }

    public function show(ActivityLog $activityLog): View
    {
        $activityLog->load('user');

        return view('admin.activity-logs.show', [
            'log' => $activityLog,
        ]);
    }

    public function activeSessions(): View
    {
        $sessions = UserSession::with('user')
            ->where('is_active', true)
            ->orderBy('last_activity_at', 'desc')
            ->paginate(10);

        return view('admin.activity-logs.sessions', [
            'sessions' => $sessions,
        ]);
    }

    public function userSessions(Request $request, int $userId): View
    {
        if (! auth()->user()->hasRole('admin') && $userId !== auth()->id()) {
            abort(403, 'You can only view your own sessions.');
        }

        $sessions = UserSession::where('user_id', $userId)
            ->orderBy('started_at', 'desc')
            ->paginate(10);

        $activeCount = UserSession::where('user_id', $userId)
            ->where('is_active', true)
            ->count();

        return view('admin.activity-logs.user-sessions', [
            'sessions' => $sessions,
            'userId' => $userId,
            'activeCount' => $activeCount,
        ]);
    }

    public function terminateSession(int $sessionId)
    {
        $session = UserSession::findOrFail($sessionId);
        $session->markAsEnded('admin_terminated');

        return back()->with('status', 'Session terminated successfully.');
    }

    public function stats(): View
    {
        $stats = [
            'total_logs_today' => ActivityLog::whereDate('created_at', today())->count(),
            'total_logs_week' => ActivityLog::where('created_at', '>=', now()->subDays(7))->count(),
            'failed_logins_today' => ActivityLog::where('action', 'login.failed')
                ->whereDate('created_at', today())
                ->count(),
            'active_sessions' => UserSession::where('is_active', true)->count(),
            'unique_users_today' => ActivityLog::whereDate('created_at', today())
                ->distinct('user_id')
                ->count('user_id'),
            'top_actions' => ActivityLog::select('action')
                ->selectRaw('count(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];

        $recentFailedLogins = ActivityLog::with('user')
            ->where('action', 'login.failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.activity-logs.stats', [
            'stats' => $stats,
            'recentFailedLogins' => $recentFailedLogins,
        ]);
    }

    public function updateNotes(Request $request, ActivityLog $activityLog): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $activityLog->update(['notes' => $validated['notes']]);

        return back()->with('status', 'Notes updated successfully.');
    }

    public function updateCustomAction(Request $request, ActivityLog $activityLog): RedirectResponse
    {
        $validated = $request->validate([
            'custom_action' => 'nullable|string|max:100',
        ]);

        $activityLog->update(['custom_action' => $validated['custom_action']]);

        return back()->with('status', 'Custom action label updated successfully.');
    }

    public function destroy(ActivityLog $activityLog): RedirectResponse
    {
        $activityLog->delete();

        return redirect()->route('admin.activity-logs.index')
            ->with('status', 'Activity log deleted successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $query = ActivityLog::query();

        if ($request->filled('date_from')) {
            $query->where('created_at', '<=', $request->input('date_from'));
        }

        if ($request->filled('older_than_days')) {
            $query->where('created_at', '<=', now()->subDays($request->input('older_than_days')));
        }

        if ($request->filled('action_filter')) {
            $query->where('action', $request->input('action_filter'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $count = $query->count();
        $query->delete();

        return redirect()->route('admin.activity-logs.index')
            ->with('status', "{$count} activity log(s) deleted successfully.");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = ActivityLog::with('user');

        if (! auth()->user()->hasRole('admin')) {
            $query->where('user_id', auth()->id());
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->input('action') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $logs = $query->orderBy('created_at', 'desc')->get();
        $filename = 'activity_logs_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'User ID', 'User Name', 'Action', 'Custom Action',
                'Description', 'Notes', 'Status', 'IP Address', 'User Agent', 'Created At',
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id, $log->user_id, $log->user?->name ?? 'Unknown',
                    $log->action, $log->custom_action ?? '', $log->description,
                    $log->notes ?? '', $log->status, $log->ip_address,
                    $log->user_agent, $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
