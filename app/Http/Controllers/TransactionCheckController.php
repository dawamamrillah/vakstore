<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionCheckController extends Controller
{
    public function index(Request $request)
    {
        $transaction = null;
        $search = trim($request->get('search', ''));

        if (! empty($search)) {
            $transaction = Transaction::with(['product.game', 'product.category', 'payment', 'user'])
                ->where('invoice_number', $search)
                ->orWhere('customer_phone', $search)
                ->latest()
                ->first();
        }

        return view('tracking', compact('transaction', 'search'));
    }
}
