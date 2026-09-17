<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::where('role', 'user')->count();
        $totalTransactions = Transaction::count();
        $todayTransactions = Transaction::whereDate('created_at', today())->count();

        $successTransactions = Transaction::where('transaction_status', 'success')->count();
        $pendingTransactions = Transaction::where('transaction_status', 'pending')->count();
        $failedTransactions = Transaction::whereIn('transaction_status', ['failed', 'refunded'])->count();

        // Financial calculations
        $totalOmzet = (float) Transaction::where('payment_status', 'paid')->sum('total');
        $totalCost = (float) Transaction::where('payment_status', 'paid')->sum('cost_price');
        $totalProfit = (float) Transaction::where('payment_status', 'paid')->sum('profit');
        $totalDiscount = (float) Transaction::where('payment_status', 'paid')->sum('discount');
        $totalRefund = (float) Transaction::where('payment_status', 'refunded')->sum('total');

        $successRate = $totalTransactions > 0 ? round(($successTransactions / $totalTransactions) * 100, 2) : 100;

        $recentTransactions = Transaction::with(['product.game', 'user', 'payment'])
            ->latest()
            ->take(10)
            ->get();

        $recentAuditLogs = AuditLog::with('user')->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalTransactions',
            'todayTransactions',
            'successTransactions',
            'pendingTransactions',
            'failedTransactions',
            'totalOmzet',
            'totalCost',
            'totalProfit',
            'totalDiscount',
            'totalRefund',
            'successRate',
            'recentTransactions',
            'recentAuditLogs'
        ));
    }
}
