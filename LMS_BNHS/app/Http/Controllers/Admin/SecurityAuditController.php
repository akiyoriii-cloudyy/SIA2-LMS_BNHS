<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityAuditLog;
use App\Models\SecurityAlert;
use App\Services\SecurityAuditor;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecurityAuditController extends Controller
{
    private SecurityAuditor $securityAuditor;

    public function __construct(SecurityAuditor $securityAuditor)
    {
        $this->securityAuditor = $securityAuditor;
    }

    public function index(Request $request): View
    {
        $query = SecurityAuditLog::with(['user', 'resolvedBy'])
            ->orderBy('created_at', 'desc');

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Filter by resolution status
        if ($request->filled('resolved')) {
            $resolved = $request->input('resolved') === 'resolved';
            $query->where('is_resolved', $resolved);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $logs = $query->paginate(10);
        $eventTypes = SecurityAuditLog::distinct()->pluck('event_type');
        $severities = ['low', 'medium', 'high', 'critical'];

        return view('admin.security-audit.index', [
            'logs' => $logs,
            'eventTypes' => $eventTypes,
            'severities' => $severities,
            'metrics' => $this->securityAuditor->getSecurityMetrics(),
        ]);
    }

    public function show(SecurityAuditLog $securityAuditLog): View
    {
        return view('admin.security-audit.show', [
            'log' => $securityAuditLog->load(['user', 'resolvedBy']),
        ]);
    }

    public function resolve(Request $request, SecurityAuditLog $securityAuditLog)
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $securityAuditLog->markAsResolved(
            auth()->id(),
            $validated['resolution_notes']
        );

        return redirect()->route('admin.security-audit.index')
            ->with('status', 'Security audit log resolved successfully.');
    }

    public function alerts(Request $request): View
    {
        $query = SecurityAlert::with('acknowledgedBy')
            ->orderBy('created_at', 'desc');

        // Filter by alert type
        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->input('alert_type'));
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'acknowledged') {
                $query->where('is_acknowledged', true);
            } elseif ($status === 'unacknowledged') {
                $query->where('is_acknowledged', false);
            }
        }

        $alerts = $query->paginate(10);
        $alertTypes = SecurityAlert::distinct()->pluck('alert_type');
        $severities = ['low', 'medium', 'high', 'critical'];

        return view('admin.security-audit.alerts', [
            'alerts' => $alerts,
            'alertTypes' => $alertTypes,
            'severities' => $severities,
            'metrics' => $this->securityAuditor->getSecurityMetrics(),
        ]);
    }

    public function acknowledge(Request $request, SecurityAlert $securityAlert)
    {
        $validated = $request->validate([
            'acknowledgment_notes' => 'nullable|string|max:1000',
        ]);

        $securityAlert->acknowledge(
            auth()->id(),
            $validated['acknowledgment_notes']
        );

        return redirect()->route('admin.security-audit.alerts')
            ->with('status', 'Security alert acknowledged successfully.');
    }

    public function deactivate(SecurityAlert $securityAlert)
    {
        $securityAlert->deactivate();

        return redirect()->route('admin.security-audit.alerts')
            ->with('status', 'Security alert deactivated successfully.');
    }

    public function reports(): View
    {
        $metrics = $this->securityAuditor->getSecurityMetrics();
        
        // Generate detailed analytics
        $reportData = [
            'daily_stats' => $this->generateDailyStats(),
            'weekly_trends' => $this->generateWeeklyTrends(),
            'top_threats' => $this->getTopThreats(),
            'ip_analysis' => $this->getIpAnalysis(),
            'user_analysis' => $this->getUserAnalysis(),
        ];

        return view('admin.security-audit.reports', [
            'metrics' => $metrics,
            'reportData' => $reportData,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $logs = SecurityAuditLog::with(['user', 'resolvedBy'])
            ->whereBetween('created_at', [$startDate, $endDate . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = "security_audit_report_{$startDate}_to_{$endDate}.csv";

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($handle, [
                'ID', 'Event Type', 'Severity', 'Description', 'User', 
                'IP Address', 'User Agent', 'Location', 'Status', 
                'Resolved By', 'Resolution Notes', 'Created At'
            ]);

            // CSV data
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->event_type,
                    $log->severity,
                    $log->description,
                    $log->user?->name ?? 'N/A',
                    $log->ip_address,
                    $log->user_agent,
                    $log->location,
                    $log->is_resolved ? 'Resolved' : 'Unresolved',
                    $log->resolvedBy?->name ?? 'N/A',
                    $log->resolution_notes,
                    $log->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename);
    }

    private function generateDailyStats(): array
    {
        $stats = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays($i);
            $stats[$date->format('Y-m-d')] = [
                'failed_logins' => SecurityAuditLog::where('event_type', 'failed_login')
                    ->whereDate('created_at', $date)->count(),
                'security_breaches' => SecurityAuditLog::where('event_type', 'security_breach')
                    ->whereDate('created_at', $date)->count(),
                'suspicious_activities' => SecurityAuditLog::where('event_type', 'suspicious_activity')
                    ->whereDate('created_at', $date)->count(),
                'alerts_generated' => SecurityAlert::whereDate('created_at', $date)->count(),
            ];
        }
        return array_reverse($stats);
    }

    private function generateWeeklyTrends(): array
    {
        $trends = [];
        for ($i = 0; $i < 4; $i++) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            
            $trends["Week " . ($i + 1)] = [
                'failed_logins' => SecurityAuditLog::where('event_type', 'failed_login')
                    ->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'security_breaches' => SecurityAuditLog::where('event_type', 'security_breach')
                    ->whereBetween('created_at', [$weekStart, $weekEnd])->count(),
                'alerts_generated' => SecurityAlert::whereBetween('created_at', [$weekStart, $weekEnd])->count(),
            ];
        }
        return array_reverse($trends);
    }

    private function getTopThreats(): array
    {
        return SecurityAuditLog::select('event_type', 'severity')
            ->selectRaw('COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('event_type', 'severity')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getIpAnalysis(): array
    {
        return SecurityAuditLog::select('ip_address')
            ->selectRaw('COUNT(*) as failed_attempts')
            ->where('event_type', 'failed_login')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('ip_address')
            ->having('failed_attempts', '>', 1)
            ->orderByDesc('failed_attempts')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getUserAnalysis(): array
    {
        return SecurityAuditLog::with('user:name,email')
            ->select('user_id')
            ->selectRaw('COUNT(*) as incident_count')
            ->whereNotNull('user_id')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('user_id')
            ->orderByDesc('incident_count')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
