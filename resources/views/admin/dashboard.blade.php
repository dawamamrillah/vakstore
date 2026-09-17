@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-12">
    
    <!-- Top System Telemetry Header -->
    <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 pb-2">
        <div>
            <div class="flex items-center gap-1 text-[#3B432D]">
                <span class="material-symbols-outlined text-sm">shield_person</span>
                <span class="text-xs uppercase tracking-widest text-[#3B432D] font-bold">Super Admin Telemetry • Core V.4.2</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#1A2016] tracking-tight mt-1">Pusat Kontrol & Manajemen Transaksi</h1>
            <p class="text-xs text-[#5C6454]">Konsol pengawasan profitabilitas, rekonsiliasi margin, dan diagnostik gateway real-time.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-2 bg-[#FFFFFF] border border-[#DCD1C2] px-3.5 py-1.5 rounded-xl shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs text-[#1A2016]">Mock Provider: <strong class="text-emerald-700">ACTIVE</strong></span>
            </div>
            <div class="flex items-center gap-2 bg-[#FFFFFF] border border-[#DCD1C2] px-3.5 py-1.5 rounded-xl shadow-xs">
                <span class="material-symbols-outlined text-emerald-600 text-sm">bolt</span>
                <span class="text-xs text-[#1A2016]">Gateway Latency: <strong class="text-emerald-700">12ms</strong></span>
            </div>
        </div>
    </div>

    <!-- Telemetry Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Omzet -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-[10px] text-[#5C6454] uppercase font-extrabold tracking-wider">Total Omzet Bruto</span>
                <span class="material-symbols-outlined text-emerald-700 text-lg">payments</span>
            </div>
            <div class="my-2">
                <h3 class="text-2xl font-extrabold text-[#1A2016]">Rp {{ number_format($totalOmzet, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-emerald-700 font-bold">100% Tercatat Snapshot</span>
            </div>
        </div>

        <!-- Total Modal -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-[10px] text-[#5C6454] uppercase font-extrabold tracking-wider">Total Modal Provider</span>
                <span class="material-symbols-outlined text-slate-600 text-lg">inventory_2</span>
            </div>
            <div class="my-2">
                <h3 class="text-2xl font-extrabold text-[#5C6454]">Rp {{ number_format($totalCost, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-[#5C6454]">Biaya Pokok Produk</span>
            </div>
        </div>

        <!-- Total Laba Bersih -->
        <div class="bg-[#FFFFFF] border border-emerald-300 rounded-2xl p-5 shadow-xs flex flex-col justify-between bg-emerald-50/40">
            <div class="flex justify-between items-start">
                <span class="text-[10px] text-emerald-800 uppercase font-extrabold tracking-wider">Total Laba Bersih (Profit)</span>
                <span class="material-symbols-outlined text-emerald-700 text-lg">trending_up</span>
            </div>
            <div class="my-2">
                <h3 class="text-2xl font-extrabold text-emerald-800">Rp {{ number_format($totalProfit, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-emerald-700 font-bold">Margin Margin Terkalkulasi</span>
            </div>
        </div>

        <!-- Total Transaksi -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs flex flex-col justify-between">
            <div class="flex justify-between items-start">
                <span class="text-[10px] text-[#5C6454] uppercase font-extrabold tracking-wider">Total Transaksi</span>
                <span class="material-symbols-outlined text-[#525A43] text-lg">sync_alt</span>
            </div>
            <div class="my-2">
                <h3 class="text-2xl font-extrabold text-[#1A2016]">{{ $totalTransactions }} Pesanan</h3>
                <span class="text-[10px] text-[#5C6454]">{{ $todayTransactions }} Transaksi Hari Ini</span>
            </div>
        </div>

    </div>

    <!-- Secondary Telemetry Row -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-[#F6F0E8] border border-[#DCD1C2] p-4 rounded-2xl text-center">
            <span class="text-[10px] uppercase font-bold text-emerald-800">Sukses</span>
            <p class="text-xl font-extrabold text-emerald-800 mt-0.5">{{ $successTransactions }}</p>
        </div>
        <div class="bg-[#F6F0E8] border border-[#DCD1C2] p-4 rounded-2xl text-center">
            <span class="text-[10px] uppercase font-bold text-amber-800">Pending</span>
            <p class="text-xl font-extrabold text-amber-800 mt-0.5">{{ $pendingTransactions }}</p>
        </div>
        <div class="bg-[#F6F0E8] border border-[#DCD1C2] p-4 rounded-2xl text-center">
            <span class="text-[10px] uppercase font-bold text-red-800">Gagal / Refund</span>
            <p class="text-xl font-extrabold text-red-800 mt-0.5">{{ $failedTransactions }}</p>
        </div>
        <div class="bg-[#F6F0E8] border border-[#DCD1C2] p-4 rounded-2xl text-center">
            <span class="text-[10px] uppercase font-bold text-[#1A2016]">Total Diskon Voucher</span>
            <p class="text-xl font-extrabold text-[#1A2016] mt-0.5">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Real-time Live Monitor Table -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#DCD1C2]">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <h3 class="text-base font-extrabold text-[#1A2016]">Antrian Transaksi Real-Time</h3>
            </div>
            <a href="{{ route('admin.transactions') }}" class="text-xs font-bold text-[#525A43] hover:underline flex items-center gap-1">
                <span>Buka Seluruh Transaksi</span>
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="text-[10px] text-[#5C6454] uppercase font-bold border-b border-[#DCD1C2]">
                        <th class="pb-3">Invoice</th>
                        <th class="pb-3">Pelanggan / Target</th>
                        <th class="pb-3">Produk</th>
                        <th class="pb-3">Modal</th>
                        <th class="pb-3">Jual</th>
                        <th class="pb-3">Laba (Profit)</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#DCD1C2]/60">
                    @forelse($recentTransactions as $tx)
                        <tr>
                            <td class="py-3 font-mono font-bold text-[#1A2016]">{{ $tx->invoice_number }}</td>
                            <td class="py-3">
                                <div class="flex flex-col">
                                    <span class="font-bold text-[#1A2016]">{{ $tx->customer_name ?? 'Guest' }}</span>
                                    <span class="text-[10px] text-[#5C6454]">{{ $tx->target }}</span>
                                </div>
                            </td>
                            <td class="py-3 font-bold text-[#1A2016]">{{ $tx->product->name }}</td>
                            <td class="py-3 text-[#5C6454]">Rp {{ number_format($tx->cost_price, 0, ',', '.') }}</td>
                            <td class="py-3 font-bold text-[#1A2016]">Rp {{ number_format($tx->total, 0, ',', '.') }}</td>
                            <td class="py-3 font-extrabold text-emerald-800">+ Rp {{ number_format($tx->profit, 0, ',', '.') }}</td>
                            <td class="py-3">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $tx->isSuccess() ? 'bg-emerald-100 text-emerald-800' : ($tx->transaction_status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                    {{ strtoupper($tx->transaction_status) }}
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                <a href="{{ route('admin.transactions.show', $tx->id) }}" class="px-3 py-1 bg-[#F6F0E8] hover:bg-[#525A43] hover:text-white rounded-lg font-bold transition-colors">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-xs text-[#5C6454]">Belum ada data transaksi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Audit Logs -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]">
            <h3 class="text-sm font-extrabold text-[#1A2016] flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">history</span>
                Audit Trail Aktivitas Admin & Finansial
            </h3>
        </div>

        <div class="divide-y divide-[#DCD1C2]/60 text-xs">
            @forelse($recentAuditLogs as $log)
                <div class="py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <div class="flex flex-col">
                            <span class="font-bold text-[#1A2016]">{{ strtoupper(str_replace('_', ' ', $log->action)) }}</span>
                            <span class="text-[10px] text-[#5C6454]">Dilakukan oleh: {{ $log->user->name ?? 'System' }} (IP: {{ $log->ip_address }})</span>
                        </div>
                    </div>
                    <span class="text-[10px] text-[#5C6454]">{{ $log->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="py-4 text-center text-xs text-[#5C6454]">Belum ada catatan audit log.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
