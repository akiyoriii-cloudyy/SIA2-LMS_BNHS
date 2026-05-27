<?php

namespace App\Services;

use App\Models\BusinessTransaction;
use App\Models\TransactionLog;
use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionManager
{
    private DatabaseManager $db;
    private ?BusinessTransaction $currentTransaction = null;
    private array $operations = [];
    private array $rollbackData = [];

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Begin a new business transaction
     */
    public function begin(string $transactionType, array $transactionData, ?string $description = null, ?int $userId = null, ?int $performedBy = null): self
    {
        $this->currentTransaction = BusinessTransaction::create([
            'transaction_id' => $this->generateTransactionId(),
            'transaction_type' => $transactionType,
            'status' => 'pending',
            'user_id' => $userId,
            'performed_by' => $performedBy ?? $userId,
            'transaction_data' => $transactionData,
            'description' => $description,
            'started_at' => now(),
        ]);

        $this->operations = [];
        $this->rollbackData = [];

        Log::info('Business transaction started', [
            'transaction_id' => $this->currentTransaction->transaction_id,
            'type' => $transactionType,
            'user_id' => $userId,
        ]);

        return $this;
    }

    /**
     * Execute a database operation within the transaction
     */
    public function execute(string $operation, string $table, callable $callback, ?string $recordId = null): mixed
    {
        if (!$this->currentTransaction) {
            throw new Exception('No active transaction. Call begin() first.');
        }

        $startTime = microtime(true);
        $oldValues = null;
        $newValues = null;
        $wasSuccessful = false;
        $errorMessage = null;

        try {
            // Get old values for update/delete operations
            if (in_array($operation, ['update', 'delete'])) {
                $oldValues = $this->getCurrentValues($table, $recordId);
            }

            // Execute the operation
            $result = $callback();

            // Get new values for insert/update operations
            if (in_array($operation, ['insert', 'update'])) {
                $newValues = $this->getCurrentValues($table, $recordId);
            }

            $wasSuccessful = true;

            // Store rollback data
            $this->storeRollbackData($operation, $table, $recordId, $oldValues);

            // Log the operation
            $this->logOperation($operation, $table, $recordId, $oldValues, $newValues, $wasSuccessful, null, $startTime);

            $this->operations[] = [
                'operation' => $operation,
                'table' => $table,
                'record_id' => $recordId,
                'old_values' => $oldValues,
                'new_values' => $newValues,
            ];

            return $result;

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $wasSuccessful = false;

            // Log the failed operation
            $this->logOperation($operation, $table, $recordId, $oldValues, $newValues, $wasSuccessful, $errorMessage, $startTime);

            Log::error('Transaction operation failed', [
                'transaction_id' => $this->currentTransaction->transaction_id,
                'operation' => $operation,
                'table' => $table,
                'error' => $errorMessage,
            ]);

            throw $e;
        }
    }

    /**
     * Commit the transaction
     */
    public function commit(): bool
    {
        if (!$this->currentTransaction) {
            throw new Exception('No active transaction to commit.');
        }

        try {
            // Start database transaction
            $this->db->beginTransaction();

            try {
                // All operations have already been executed
                // Just mark the business transaction as committed
                $this->currentTransaction->markAsCommitted();

                // Commit database transaction
                $this->db->commit();

                Log::info('Business transaction committed', [
                    'transaction_id' => $this->currentTransaction->transaction_id,
                    'operations_count' => count($this->operations),
                ]);

                return true;

            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            $this->currentTransaction->markAsFailed($e->getMessage());
            throw $e;
        } finally {
            $this->currentTransaction = null;
            $this->operations = [];
            $this->rollbackData = [];
        }
    }

    /**
     * Rollback the transaction
     */
    public function rollback(?string $reason = null): bool
    {
        if (!$this->currentTransaction) {
            throw new Exception('No active transaction to rollback.');
        }

        try {
            // Start database transaction for rollback
            $this->db->beginTransaction();

            try {
                // Execute rollback operations in reverse order
                $rollbackOperations = array_reverse($this->rollbackData);

                foreach ($rollbackOperations as $rollbackOp) {
                    $this->executeRollbackOperation($rollbackOp);
                }

                // Mark the business transaction as rolled back
                $this->currentTransaction->markAsRolledBack($reason);

                // Commit database transaction
                $this->db->commit();

                Log::info('Business transaction rolled back', [
                    'transaction_id' => $this->currentTransaction->transaction_id,
                    'reason' => $reason,
                    'operations_count' => count($rollbackOperations),
                ]);

                return true;

            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            $this->currentTransaction->markAsFailed($e->getMessage());
            throw $e;
        } finally {
            $this->currentTransaction = null;
            $this->operations = [];
            $this->rollbackData = [];
        }
    }

    /**
     * Get current transaction
     */
    public function getCurrentTransaction(): ?BusinessTransaction
    {
        return $this->currentTransaction;
    }

    /**
     * Check if there's an active transaction
     */
    public function hasActiveTransaction(): bool
    {
        return $this->currentTransaction !== null;
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId(): string
    {
        return 'txn_' . uniqid() . '_' . time();
    }

    /**
     * Get current values from database
     */
    private function getCurrentValues(string $table, ?string $recordId): ?array
    {
        if (!$recordId) {
            return null;
        }

        try {
            $record = $this->db->table($table)->where('id', $recordId)->first();
            return $record ? (array) $record : null;
        } catch (Exception $e) {
            Log::warning('Failed to get current values for rollback data', [
                'table' => $table,
                'record_id' => $recordId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Store rollback data
     */
    private function storeRollbackData(string $operation, string $table, ?string $recordId, ?array $oldValues): void
    {
        $this->rollbackData[] = [
            'operation' => $operation,
            'table' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues,
        ];
    }

    /**
     * Execute rollback operation
     */
    private function executeRollbackOperation(array $rollbackOp): void
    {
        $operation = $rollbackOp['operation'];
        $table = $rollbackOp['table'];
        $recordId = $rollbackOp['record_id'];
        $oldValues = $rollbackOp['old_values'];

        try {
            switch ($operation) {
                case 'insert':
                    // Rollback insert by deleting the record
                    if ($recordId) {
                        $this->db->table($table)->where('id', $recordId)->delete();
                    }
                    break;

                case 'update':
                    // Rollback update by restoring old values
                    if ($oldValues && $recordId) {
                        $this->db->table($table)->where('id', $recordId)->update($oldValues);
                    }
                    break;

                case 'delete':
                    // Rollback delete by restoring the record
                    if ($oldValues) {
                        $this->db->table($table)->insert($oldValues);
                    }
                    break;
            }
        } catch (Exception $e) {
            Log::error('Failed to execute rollback operation', [
                'operation' => $operation,
                'table' => $table,
                'record_id' => $recordId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Log operation details
     */
    private function logOperation(string $operation, string $table, ?string $recordId, ?array $oldValues, ?array $newValues, bool $wasSuccessful, ?string $errorMessage, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        TransactionLog::create([
            'transaction_id' => $this->currentTransaction->transaction_id,
            'operation' => $operation,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'execution_time_ms' => $executionTime,
            'was_successful' => $wasSuccessful,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStats(): array
    {
        $now = now();
        $last24Hours = $now->copy()->subDay();
        $lastWeek = $now->copy()->subWeek();

        return [
            'total_transactions' => BusinessTransaction::count(),
            'transactions_24h' => BusinessTransaction::where('started_at', '>=', $last24Hours)->count(),
            'transactions_week' => BusinessTransaction::where('started_at', '>=', $lastWeek)->count(),
            'pending_transactions' => BusinessTransaction::where('status', 'pending')->count(),
            'failed_transactions' => BusinessTransaction::where('status', 'failed')->count(),
            'success_rate' => $this->calculateSuccessRate(),
            'average_duration' => $this->calculateAverageDuration(),
        ];
    }

    /**
     * Calculate transaction success rate
     */
    private function calculateSuccessRate(): float
    {
        $total = BusinessTransaction::whereIn('status', ['committed', 'rolled_back', 'failed'])->count();
        $successful = BusinessTransaction::whereIn('status', ['committed', 'rolled_back'])->count();

        return $total > 0 ? round(($successful / $total) * 100, 2) : 0;
    }

    /**
     * Calculate average transaction duration
     */
    private function calculateAverageDuration(): ?float
    {
        $transactions = BusinessTransaction::whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->get(['started_at', 'completed_at']);

        if ($transactions->isEmpty()) {
            return null;
        }

        $totalDuration = $transactions->sum(function ($transaction) {
            return $transaction->completed_at->diffInSeconds($transaction->started_at);
        });

        return round($totalDuration / $transactions->count(), 2);
    }
}
