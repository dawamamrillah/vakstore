<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\Provider\GameTopupService;
use App\Services\Transaction\TransactionService;
use App\Services\Voucher\VoucherService;
use Exception;
use Illuminate\Http\Request;

class TopUpController extends Controller
{
    public function __construct(
        protected GameTopupService $gameTopupService,
        protected TransactionService $transactionService,
        protected VoucherService $voucherService
    ) {}

    public function index()
    {
        $games = Game::with(['products' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order')])
            ->whereHas('category', fn ($q) => $q->where('type', 'game'))
            ->where('status', 'active')
            ->get();

        return view('topup.index', compact('games'));
    }

    public function show(string $slug)
    {
        $game = Game::with(['products' => function ($q) {
            $q->where('status', 'active')->orderBy('cost_price');
        }])->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // Group products by sub_category
        $groupedProducts = $game->products->groupBy('sub_category');

        return view('topup.show', compact('game', 'groupedProducts'));
    }

    public function checkAccount(Request $request)
    {
        $request->validate([
            'game_slug' => 'required|string',
            'user_id' => 'required|string',
            'zone_id' => 'nullable|string',
        ]);

        try {
            $result = $this->gameTopupService->checkAccount(
                $request->game_slug,
                $request->user_id,
                $request->zone_id
            );

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'target' => 'required|string',
            'target_secondary' => 'nullable|string',
            'nickname' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'voucher_code' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'customer_phone' => 'nullable|string',
            'customer_email' => 'nullable|email',
            'idempotency_key' => 'nullable|string',
        ]);

        try {
            $user = auth()->user();
            $transaction = $this->transactionService->createOrder($request->all(), $user);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pesanan berhasil dibuat.',
                    'invoice_number' => $transaction->invoice_number,
                    'redirect_url' => route('invoice.show', $transaction->invoice_number),
                    'transaction' => $transaction,
                ]);
            }

            return redirect()->route('invoice.show', $transaction->invoice_number)
                ->with('success', 'Transaksi berhasil diproses!');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
