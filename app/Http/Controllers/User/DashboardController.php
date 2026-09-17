<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Voucher;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user()->load('wallet');

        $totalTransactions = Transaction::where('user_id', $user->id)->count();
        $successTransactions = Transaction::where('user_id', $user->id)->where('transaction_status', 'success')->count();
        $pendingTransactions = Transaction::where('user_id', $user->id)->where('transaction_status', 'pending')->count();
        $failedTransactions = Transaction::where('user_id', $user->id)->whereIn('transaction_status', ['failed', 'refunded'])->count();

        $recentTransactions = Transaction::with(['product.game', 'payment'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $vouchers = Voucher::where('status', 'active')->take(2)->get();

        return view('user.dashboard', compact(
            'user',
            'totalTransactions',
            'successTransactions',
            'pendingTransactions',
            'failedTransactions',
            'recentTransactions',
            'vouchers'
        ));
    }
}
