<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user()->load('wallet');

        $query = WalletTransaction::where('user_id', $user->id);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $mutations = $query->latest()->paginate(10)->withQueryString();

        return view('user.wallet', compact('user', 'mutations'));
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000|max:10000000',
            'payment_method' => 'required|string',
        ]);

        try {
            $user = auth()->user();
            $amount = (float) $request->amount;
            $ref = 'DEP-'.date('Ymd').'-'.Str::upper(Str::random(6));

            // Instant credit for mock gateway demo
            $this->walletService->credit(
                $user,
                $amount,
                'deposit',
                $ref,
                'Top Up Saldo Vault via '.strtoupper(str_replace('_', ' ', $request->payment_method))
            );

            return redirect()->route('user.wallet')
                ->with('success', 'Top up Saldo Vault sebesar Rp '.number_format($amount, 0, ',', '.').' berhasil ditambahkan!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
