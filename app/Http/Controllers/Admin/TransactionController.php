<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\Audit\AuditService;
use App\Services\Transaction\TransactionService;
use Exception;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request)
    {
        $query = Transaction::with(['product.game', 'product.category', 'user', 'payment']);

        // Date Filters
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('created_at', '>=', $request->start_date)
                ->whereDate('created_at', '<=', $request->end_date);
        } elseif ($request->filled('preset')) {
            switch ($request->preset) {
                case 'today':
                    $query->whereDate('created_at', now()->toDateString());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', now()->subDay()->toDateString());
                    break;
                case '7days':
                    $query->whereDate('created_at', '>=', now()->subDays(7)->toDateString());
                    break;
                case '30days':
                    $query->whereDate('created_at', '>=', now()->subDays(30)->toDateString());
                    break;
                case 'this_month':
                    $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year);
                    break;
            }
        }

        if ($request->filled('status')) {
            $query->where('transaction_status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('target', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Compute summary metrics for the filtered dataset
        $metricsQuery = clone $query;
        $totalTransactions = $metricsQuery->count();
        $totalRevenue = (clone $query)->where('payment_status', 'paid')->sum('total');
        $totalCost = (clone $query)->where('payment_status', 'paid')->sum('cost_price');
        $totalProfit = (clone $query)->where('payment_status', 'paid')->sum('profit');
        $totalSuccess = (clone $query)->where('transaction_status', 'success')->count();
        $totalPending = (clone $query)->where('transaction_status', 'pending')->count();
        $totalFailed = (clone $query)->whereIn('transaction_status', ['failed', 'refunded'])->count();

        // Always latest first (ORDER BY created_at DESC)
        $transactions = $query->latest()->paginate(15)->withQueryString();

        return view('admin.transactions.index', compact(
            'transactions',
            'totalTransactions',
            'totalRevenue',
            'totalCost',
            'totalProfit',
            'totalSuccess',
            'totalPending',
            'totalFailed'
        ));
    }

    public function show(string $id)
    {
        $transaction = Transaction::with(['product.game', 'product.category', 'user', 'payment', 'provider'])
            ->findOrFail($id);

        return view('admin.transactions.show', compact('transaction'));
    }

    public function refund(Request $request, string $id)
    {
        $transaction = Transaction::findOrFail($id);

        try {
            $reason = $request->input('reason', 'Refund manual oleh Admin');

            $oldData = $transaction->toArray();
            $updated = $this->transactionService->manualRefund($transaction, $reason);

            AuditService::log(
                'refund_transaction',
                Transaction::class,
                $transaction->id,
                $oldData,
                $updated->toArray(),
                auth()->user()
            );

            return back()->with('success', 'Transaksi '.$transaction->invoice_number.' berhasil di-refund!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
