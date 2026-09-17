<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Transaction;
use App\Models\Voucher;

class HomeController extends Controller
{
    public function index()
    {
        $allServices = Game::with(['category', 'products' => function ($q) {
            $q->where('status', 'active')->orderBy('cost_price');
        }])
            ->where('status', 'active')
            ->get();

        $gameServices = $allServices->filter(fn ($g) => $g->category?->type === 'game');

        $allowedPulsaSlugs = ['telkomsel', 'axis', 'xl', 'tri', 'smartfren', 'indosat', 'byu'];
        $pulsaServices = $allServices->filter(fn ($g) => $g->category?->slug === 'pulsa-all-operator' && in_array($g->slug, $allowedPulsaSlugs))
            ->sortBy(fn ($g) => array_search($g->slug, $allowedPulsaSlugs))
            ->values();

        $plnServices = $allServices->filter(fn ($g) => $g->category?->slug === 'pln');
        $pascaServices = $allServices->filter(fn ($g) => $g->category?->slug === 'tagihan-pascabayar');

        $vouchers = Voucher::where('status', 'active')->take(3)->get();
        $featuredPromo = Voucher::where('code', 'TOPUPHEMAT')->first() ?? $vouchers->first();

        // Calculate live ticker stats
        $totalTransactions = Transaction::count();
        $successfulTransactions = Transaction::where('transaction_status', 'success')->count();
        $successRate = $totalTransactions > 0 ? round(($successfulTransactions / $totalTransactions) * 100, 2) : 99.98;

        return view('home', compact(
            'allServices',
            'gameServices',
            'pulsaServices',
            'plnServices',
            'pascaServices',
            'vouchers',
            'featuredPromo',
            'successRate',
            'totalTransactions'
        ));
    }
}
