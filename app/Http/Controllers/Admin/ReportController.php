<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Product Profitability Report
        $productReports = Transaction::where('payment_status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->select(
                'product_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as gross_revenue'),
                DB::raw('SUM(cost_price) as total_cost'),
                DB::raw('SUM(discount) as total_discount'),
                DB::raw('SUM(profit) as net_profit')
            )
            ->groupBy('product_id')
            ->with('product.game')
            ->orderByDesc('net_profit')
            ->get();

        $overallRevenue = $productReports->sum('gross_revenue');
        $overallCost = $productReports->sum('total_cost');
        $overallProfit = $productReports->sum('net_profit');
        $overallOrders = $productReports->sum('total_orders');

        return view('admin.reports.index', compact(
            'productReports',
            'startDate',
            'endDate',
            'overallRevenue',
            'overallCost',
            'overallProfit',
            'overallOrders'
        ));
    }
}
