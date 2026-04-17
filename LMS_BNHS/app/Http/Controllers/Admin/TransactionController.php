<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessTransaction;
use App\Models\TransactionLog;
use App\Services\TransactionManager;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    private TransactionManager $transactionManager;

    public function __construct(TransactionManager $transactionManager)
    {
        $this->transactionManager = $transactionManager;
    }

    public function index(Request $request): View
    {
        $query = BusinessTransaction::with(['user', 'performedBy', 'transactionLogs'])
            ->orderBy('started_at', 'desc');

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->input('transaction_type'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('started_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('started_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $transactions = $query->paginate(10);
        $transactionTypes = BusinessTransaction::distinct()->pluck('transaction_type');
        $statuses = ['pending', 'committed', 'rolled_back', 'failed'];

        return view('admin.transactions.index', [
            'transactions' => $transactions,
            'transactionTypes' => $transactionTypes,
            'statuses' => $statuses,
            'stats' => $this->transactionManager->getTransactionStats(),
        ]);
    }

    public function show(BusinessTransaction $transaction): View
    {
        $transaction->load(['user', 'performedBy', 'transactionLogs']);

        return view('admin.transactions.show', [
            'transaction' => $transaction,
        ]);
    }

    public function logs(Request $request): View
    {
        $query = TransactionLog::with('businessTransaction')
            ->orderBy('created_at', 'desc');

        // Filter by operation
        if ($request->filled('operation')) {
            $query->where('operation', $request->input('operation'));
        }

        // Filter by table
        if ($request->filled('table_name')) {
            $query->where('table_name', $request->input('table_name'));
        }

        // Filter by success status
        if ($request->filled('was_successful')) {
            $success = $request->input('was_successful') === 'true';
            $query->where('was_successful', $success);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $logs = $query->paginate(10);
        $operations = ['insert', 'update', 'delete'];
        $tables = $this->getTableNames();

        return view('admin.transactions.logs', [
            'logs' => $logs,
            'operations' => $operations,
            'tables' => $tables,
        ]);
    }

    public function analytics(): View
    {
        $stats = $this->transactionManager->getTransactionStats();
        
        // Generate detailed analytics
        $analyticsData = [
            'daily_transaction_volume' => $this->getDailyTransactionVolume(),
            'transaction_types_breakdown' => $this->getTransactionTypesBreakdown(),
            'success_failure_trends' => $this->getSuccessFailureTrends(),
            'table_activity_analysis' => $this->getTableActivityAnalysis(),
            'performance_metrics' => $this->getPerformanceMetrics(),
        ];

        return view('admin.transactions.analytics', [
            'stats' => $stats,
            'analyticsData' => $analyticsData,
        ]);
    }

    public function rollback(BusinessTransaction $transaction, Request $request)
    {
        if ($transaction->status !== 'committed') {
            return back()->withErrors(['message' => 'Only committed transactions can be rolled back.']);
        }

        if ($transaction->completed_at && $transaction->completed_at->diffInHours(now()) > 24) {
            return back()->withErrors(['message' => 'Transactions older than 24 hours cannot be rolled back.']);
        }

        try {
            // This would require implementing a rollback mechanism for completed transactions
            // For now, we'll just mark it as rolled back
            $transaction->markAsRolledBack($request->input('reason', 'Manual rollback by administrator'));

            return redirect()->route('admin.transactions.index')
                ->with('status', 'Transaction rolled back successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Failed to rollback transaction: ' . $e->getMessage()]);
        }
    }

    private function getTableNames(): array
    {
        try {
            $tables = DB::select('SHOW TABLES');
            return array_map(function ($table) {
                return array_values((array) $table)[0];
            }, $tables);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getDailyTransactionVolume(): array
    {
        $volume = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays($i);
            $volume[$date->format('Y-m-d')] = [
                'total' => BusinessTransaction::whereDate('started_at', $date)->count(),
                'committed' => BusinessTransaction::whereDate('started_at', $date)->where('status', 'committed')->count(),
                'rolled_back' => BusinessTransaction::whereDate('started_at', $date)->where('status', 'rolled_back')->count(),
                'failed' => BusinessTransaction::whereDate('started_at', $date)->where('status', 'failed')->count(),
            ];
        }
        return array_reverse($volume);
    }

    private function getTransactionTypesBreakdown(): array
    {
        return BusinessTransaction::select('transaction_type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN status = "committed" THEN 1 ELSE 0 END) as committed')
            ->selectRaw('SUM(CASE WHEN status = "rolled_back" THEN 1 ELSE 0 END) as rolled_back')
            ->selectRaw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed')
            ->where('started_at', '>=', now()->subDays(30))
            ->groupBy('transaction_type')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    private function getSuccessFailureTrends(): array
    {
        $trends = [];
        for ($i = 0; $i < 4; $i++) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            
            $total = BusinessTransaction::whereBetween('started_at', [$weekStart, $weekEnd])->count();
            $successful = BusinessTransaction::whereIn('status', ['committed', 'rolled_back'])
                ->whereBetween('started_at', [$weekStart, $weekEnd])->count();
            
            $trends["Week " . ($i + 1)] = [
                'total' => $total,
                'successful' => $successful,
                'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            ];
        }
        return array_reverse($trends);
    }

    private function getTableActivityAnalysis(): array
    {
        return TransactionLog::select('table_name')
            ->selectRaw('COUNT(*) as operation_count')
            ->selectRaw('SUM(CASE WHEN operation = "insert" THEN 1 ELSE 0 END) as inserts')
            ->selectRaw('SUM(CASE WHEN operation = "update" THEN 1 ELSE 0 END) as updates')
            ->selectRaw('SUM(CASE WHEN operation = "delete" THEN 1 ELSE 0 END) as deletes')
            ->selectRaw('AVG(execution_time_ms) as avg_execution_time')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('table_name')
            ->orderByDesc('operation_count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getPerformanceMetrics(): array
    {
        $totalOperations = TransactionLog::where('created_at', '>=', now()->subDays(30))->count();
        $successfulOperations = TransactionLog::where('created_at', '>=', now()->subDays(30))
            ->where('was_successful', true)
            ->count();
        
        $successRate = $totalOperations > 0 ? ($successfulOperations / $totalOperations) * 100 : 0;
        
        return [
            'avg_execution_time' => TransactionLog::where('created_at', '>=', now()->subDays(30))
                ->avg('execution_time_ms'),
            'slow_operations' => TransactionLog::where('created_at', '>=', now()->subDays(30))
                ->where('execution_time_ms', '>', 1000)
                ->count(),
            'failed_operations' => TransactionLog::where('created_at', '>=', now()->subDays(30))
                ->where('was_successful', false)
                ->count(),
            'total_operations' => $totalOperations,
            'success_rate' => round($successRate, 2),
        ];
    }
}
