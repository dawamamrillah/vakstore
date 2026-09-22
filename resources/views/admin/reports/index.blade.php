@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-12">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1A2016]">Laporan Keuntungan & Profitabilitas</h1>
            <p class="text-xs text-[#5C6454]">Rekonsiliasi omzet, beban modal provider, potongan kupon, dan laba bersih per produk</p>
        </div>

        <form action="{{ route('admin.reports') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="h-9 bg-[#FFFFFF] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1A2016]">
            <span class="text-xs text-[#5C6454]">s/d</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="h-9 bg-[#FFFFFF] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1A2016]">
            <button type="submit" class="px-4 h-9 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold transition-colors">
                Filter
            </button>
        </form>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs">
            <span class="text-[10px] text-[#5C6454] uppercase font-bold">Total Transaksi Selesai</span>
            <h3 class="text-2xl font-extrabold text-[#1A2016] mt-1">{{ $overallOrders }} Pesanan</h3>
        </div>
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs">
            <span class="text-[10px] text-[#5C6454] uppercase font-bold">Total Omzet Bruto</span>
            <h3 class="text-2xl font-extrabold text-[#1A2016] mt-1">Rp {{ number_format($overallRevenue, 0, ',', '.') }}</h3>
        </div>
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs">
            <span class="text-[10px] text-[#5C6454] uppercase font-bold">Total Harga Modal</span>
            <h3 class="text-2xl font-extrabold text-[#5C6454] mt-1">Rp {{ number_format($overallCost, 0, ',', '.') }}</h3>
        </div>
        <div class="bg-[#FFFFFF] border border-emerald-300 rounded-2xl p-5 shadow-xs bg-emerald-50/40">
            <span class="text-[10px] text-emerald-800 uppercase font-extrabold">Total Laba Bersih</span>
            <h3 class="text-2xl font-extrabold text-emerald-800 mt-1">Rp {{ number_format($overallProfit, 0, ',', '.') }}</h3>
        </div>
    </div>

    <!-- Product Breakdown Table -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm">
        <h3 class="text-base font-extrabold text-[#1A2016] mb-4">Rincian Profitabilitas Berdasarkan Produk</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="text-[10px] text-[#5C6454] uppercase font-bold border-b border-[#DCD1C2]">
                        <th class="pb-3">Nama Produk</th>
                        <th class="pb-3">Kategori / Game</th>
                        <th class="pb-3 text-center">Volume Order</th>
                        <th class="pb-3 text-right">Omzet Bruto</th>
                        <th class="pb-3 text-right">Harga Modal</th>
                        <th class="pb-3 text-right">Total Diskon</th>
                        <th class="pb-3 text-right">Laba Bersih (Net Profit)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#DCD1C2]/60">
                    @forelse($productReports as $report)
                        <tr>
                            <td class="py-3 font-bold text-[#1A2016]">{{ $report->product->name ?? 'Deleted Item' }}</td>
                            <td class="py-3 text-[#5C6454]">{{ $report->product->game->name ?? $report->product->category->name ?? '-' }}</td>
                            <td class="py-3 text-center font-extrabold text-[#1A2016]">{{ $report->total_orders }}</td>
                            <td class="py-3 text-right font-bold text-[#1A2016]">Rp {{ number_format($report->gross_revenue, 0, ',', '.') }}</td>
                            <td class="py-3 text-right text-[#5C6454]">Rp {{ number_format($report->total_cost, 0, ',', '.') }}</td>
                            <td class="py-3 text-right text-emerald-700">- Rp {{ number_format($report->total_discount, 0, ',', '.') }}</td>
                            <td class="py-3 text-right font-extrabold text-emerald-800 text-sm">+ Rp {{ number_format($report->net_profit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-xs text-[#5C6454]">Belum ada data transaksi dalam periode tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
