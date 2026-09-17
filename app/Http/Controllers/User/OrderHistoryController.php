<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class OrderHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Transaction::with(['product.game', 'product.category', 'payment'])
            ->where('user_id', $user->id);

        if ($request->filled('status')) {
            $query->where('transaction_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('target', 'like', "%{$search}%");
            });
        }

        $transactions = $query->latest()->paginate(10)->withQueryString();

        return view('user.orders', compact('transactions'));
    }
}
