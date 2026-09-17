@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-12">
    
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs font-bold uppercase tracking-widest text-[#5C6454]">Laporan Transaksi Real-Time</span>
            </div>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-[#1A2016] tracking-tight mt-0.5">Manajemen &amp; Laporan Seluruh Transaksi</h1>
            <p class="text-xs text-[#5C6454] mt-1">
                Pantau seluruh transaksi terbaru (selalu urut dari yang paling baru), filter per tanggal transaksi, serta analisis omzet &amp; laba bersih.
            </p>
        </div>

        <!-- Quick View All / Reset -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.transactions') }}" class="h-10 px-4 bg-[#FFFFFF] border border-[#DCD1C2] hover:bg-[#F6F0E8] text-[#1A2016] rounded-xl text-xs font-extrabold flex items-center gap-1.5 shadow-xs transition-colors">
                <span class="material-symbols-outlined text-base">receipt_long</span>
                <span>Lihat Semua Transaksi</span>
            </a>
        </div>
    </div>

    <!-- Date Presets & Filter Bar Card -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-5 lg:p-6 shadow-sm space-y-4">
        
        <!-- Preset Filter Tabs -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-[#DCD1C2]/60">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-[#1A2016]">Pintasan Periode:</span>
                <div class="flex flex-wrap items-center gap-1.5">
                    <a 
                        href="{{ route('admin.transactions') }}" 
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ !request('preset') && !request('date') && !request('start_date') ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#5C6454] hover:bg-[#EAE1D4] hover:text-[#1A2016]' }}"
                    >
                        Semua Transaksi
                    </a>
                    <a 
                        href="{{ route('admin.transactions', ['preset' => 'today']) }}" 
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('preset') === 'today' ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#5C6454] hover:bg-[#EAE1D4] hover:text-[#1A2016]' }}"
                    >
                        Hari Ini
                    </a>
                    <a 
                        href="{{ route('admin.transactions', ['preset' => 'yesterday']) }}" 
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('preset') === 'yesterday' ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#5C6454] hover:bg-[#EAE1D4] hover:text-[#1A2016]' }}"
                    >
                        Kemarin
                    </a>
                    <a 
                        href="{{ route('admin.transactions', ['preset' => '7days']) }}" 
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('preset') === '7days' ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#5C6454] hover:bg-[#EAE1D4] hover:text-[#1A2016]' }}"
                    >
                        7 Hari Terakhir
                    </a>
                    <a 
                        href="{{ route('admin.transactions', ['preset' => 'this_month']) }}" 
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('preset') === 'this_month' ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#5C6454] hover:bg-[#EAE1D4] hover:text-[#1A2016]' }}"
                    >
                        Bulan Ini
                    </a>
                </div>
            </div>

            @if(request('date') || request('start_date') || request('preset') || request('search') || request('status'))
                <a href="{{ route('admin.transactions') }}" class="text-xs font-bold text-rose-700 hover:text-rose-900 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">close</span>
                    Reset Filter
                </a>
            @endif
        </div>

        <!-- Custom Date & Search Filter Form -->
        <form action="{{ route('admin.transactions') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <!-- Specific Single Date -->
            <div class="sm:col-span-3 flex flex-col gap-1">
                <label class="text-[11px] font-bold text-[#5C6454]">Filter Tanggal Spesifik:</label>
                <input 
                    type="date" 
                    name="date" 
                    value="{{ request('date') }}" 
                    class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                >
            </div>

            <!-- Date Range Start -->
            <div class="sm:col-span-2 flex flex-col gap-1">
                <label class="text-[11px] font-bold text-[#5C6454]">Dari Tanggal:</label>
                <input 
                    type="date" 
                    name="start_date" 
                    value="{{ request('start_date') }}" 
                    class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                >
            </div>

            <!-- Date Range End -->
            <div class="sm:col-span-2 flex flex-col gap-1">
                <label class="text-[11px] font-bold text-[#5C6454]">Sampai Tanggal:</label>
                <input 
                    type="date" 
                    name="end_date" 
                    value="{{ request('end_date') }}" 
                    class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                >
            </div>

            <!-- Status Selector -->
            <div class="sm:col-span-2 flex flex-col gap-1">
                <label class="text-[11px] font-bold text-[#5C6454]">Status Transaksi:</label>
                <select name="status" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 rounded-xl text-xs font-bold text-[#1A2016] focus:outline-none focus:border-[#525A43]">
                    <option value="">Semua Status</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Sukses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>

            <!-- Search Keyword -->
            <div class="sm:col-span-2 flex flex-col gap-1">
                <label class="text-[11px] font-bold text-[#5C6454]">Pencarian:</label>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Invoice/Target/Nama..." 
                    class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1A2016] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43]"
                >
            </div>

            <!-- Submit Button -->
            <div class="sm:col-span-1">
                <button type="submit" class="w-full h-10 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1 transition-colors shadow-xs">
                    <span class="material-symbols-outlined text-base">filter_alt</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Metrics for Active Filter -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metric 1: Total Volume -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-[10px] text-[#5C6454] uppercase font-extrabold tracking-wider">Total Transaksi</span>
                <h3 class="text-2xl font-extrabold text-[#1A2016] mt-1">{{ number_format($totalTransactions, 0, ',', '.') }} <span class="text-xs font-semibold text-[#5C6454]">Pesanan</span></h3>
                <span class="text-[11px] text-emerald-700 font-bold mt-0.5">{{ $totalSuccess }} Sukses • {{ $totalPending }} Pending</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-[#F6F0E8] border border-[#DCD1C2] flex items-center justify-center text-[#525A43]">
                <span class="material-symbols-outlined text-xl">shopping_cart</span>
            </div>
        </div>

        <!-- Metric 2: Gross Revenue -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-[10px] text-[#5C6454] uppercase font-extrabold tracking-wider">Total Omzet Bruto</span>
                <h3 class="text-2xl font-extrabold text-[#1A2016] mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</h3>
                <span class="text-[11px] text-[#5C6454] font-medium mt-0.5">Dari transaksi berstatus lunas</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-700">
                <span class="material-symbols-outlined text-xl">payments</span>
            </div>
        </div>

        <!-- Metric 3: Provider Cost -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-[10px] text-[#5C6454] uppercase font-extrabold tracking-wider">Total Modal Provider</span>
                <h3 class="text-2xl font-extrabold text-[#5C6454] mt-1">Rp {{ number_format($totalCost, 0, ',', '.') }}</h3>
                <span class="text-[11px] text-[#5C6454] font-medium mt-0.5">Beban biaya modal supplier</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700">
                <span class="material-symbols-outlined text-xl">account_balance</span>
            </div>
        </div>

        <!-- Metric 4: Net Profit -->
        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100/50 border border-emerald-300 rounded-2xl p-5 shadow-xs flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-[10px] text-emerald-800 uppercase font-extrabold tracking-wider">Laba Bersih (Net Profit)</span>
                <h3 class="text-2xl font-extrabold text-emerald-900 mt-1">+ Rp {{ number_format($totalProfit, 0, ',', '.') }}</h3>
                <span class="text-[11px] text-emerald-700 font-bold mt-0.5">Margin laba bersih terealisasi</span>
            </div>
            <div class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-xs">
                <span class="material-symbols-outlined text-xl">trending_up</span>
            </div>
        </div>
    </div>

    <!-- Transactions Table Card -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-5 lg:p-7 shadow-sm">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#DCD1C2]/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg text-[#525A43]">swap_vert</span>
                <h3 class="text-sm font-extrabold text-[#1A2016]">Daftar Transaksi (Terbaru di Paling Atas)</h3>
            </div>
            <span class="text-xs text-[#5C6454]">Menampilkan {{ $transactions->count() }} dari {{ $transactions->total() }} transaksi</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="text-[10px] text-[#5C6454] uppercase font-extrabold border-b border-[#DCD1C2]">
                        <th class="pb-3.5 pr-2">Invoice</th>
                        <th class="pb-3.5 px-2">Waktu Transaksi</th>
                        <th class="pb-3.5 px-2">Pelanggan</th>
                        <th class="pb-3.5 px-2">Produk &amp; Kategori</th>
                        <th class="pb-3.5 px-2">Target Akun</th>
                        <th class="pb-3.5 px-2 text-right">Modal</th>
                        <th class="pb-3.5 px-2 text-right">Harga Jual</th>
                        <th class="pb-3.5 px-2 text-right">Laba Bersih</th>
                        <th class="pb-3.5 px-2 text-center">Status</th>
                        <th class="pb-3.5 pl-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#DCD1C2]/60">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-[#FDFBF7] transition-colors">
                            <td class="py-3.5 pr-2 font-mono font-extrabold text-[#1A2016] whitespace-nowrap">
                                <a href="{{ route('admin.transactions.show', $tx->id) }}" class="hover:text-[#525A43] hover:underline">
                                    {{ $tx->invoice_number }}
                                </a>
                            </td>
                            <td class="py-3.5 px-2 text-[#5C6454] whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-bold text-[#1A2016]">{{ $tx->created_at->format('d/m/Y') }}</span>
                                    <span class="text-[10px]">{{ $tx->created_at->format('H:i:s') }} WIB</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-2">
                                <div class="flex flex-col">
                                    <span class="font-bold text-[#1A2016]">{{ $tx->customer_name ?? 'Guest' }}</span>
                                    <span class="text-[10px] text-[#5C6454]">{{ $tx->customer_phone ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-2">
                                <div class="flex flex-col">
                                    <span class="font-bold text-[#1A2016]">{{ $tx->product->name ?? 'Produk Dihapus' }}</span>
                                    <span class="text-[10px] text-[#5C6454] font-medium">{{ $tx->product->game->name ?? $tx->product->category->name ?? '-' }} ({{ $tx->product->sub_category ?? '-' }})</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-2 font-mono text-[#5C6454] whitespace-nowrap">
                                {{ $tx->target }} {{ $tx->target_secondary ? '('.$tx->target_secondary.')' : '' }}
                            </td>
                            <td class="py-3.5 px-2 text-right text-[#5C6454] whitespace-nowrap">
                                Rp {{ number_format($tx->cost_price, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-2 text-right font-bold text-[#1A2016] whitespace-nowrap">
                                Rp {{ number_format($tx->total, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-2 text-right font-extrabold text-emerald-800 whitespace-nowrap">
                                + Rp {{ number_format($tx->profit, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-2 text-center whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $tx->isSuccess() ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($tx->transaction_status === 'pending' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-red-100 text-red-800 border border-red-200') }}">
                                    {{ $tx->transaction_status }}
                                </span>
                            </td>
                            <td class="py-3.5 pl-2 text-right whitespace-nowrap">
                                <a href="{{ route('admin.transactions.show', $tx->id) }}" class="px-3 py-1.5 bg-[#F6F0E8] hover:bg-[#525A43] hover:text-white rounded-xl font-bold transition-colors shadow-xs">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-12 text-xs text-[#5C6454]">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-3xl text-[#878c7f]">receipt_long</span>
                                    <span>Tidak ada transaksi yang cocok dengan filter tanggal/pencarian ini.</span>
                                    <a href="{{ route('admin.transactions') }}" class="text-xs font-bold text-[#525A43] underline mt-1">Reset dan lihat semua transaksi</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-5 border-t border-[#DCD1C2]/60 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span class="text-xs text-[#5C6454]">Halaman {{ $transactions->currentPage() }} dari {{ $transactions->lastPage() }} (Total {{ $transactions->total() }} transaksi)</span>
            <div>
                {{ $transactions->links() }}
            </div>
        </div>
    </div>

</div>
@endsection
