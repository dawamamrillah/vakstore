@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-10">
    <div class="flex flex-col gap-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-[#1F2419]">Riwayat Pesanan Anda</h1>
                <p class="text-xs text-[#596152]">Daftar seluruh transaksi pembelian produk game dan tagihan PPOB</p>
            </div>

            <!-- Filter Status -->
            <form action="{{ route('user.orders') }}" method="GET" class="flex flex-wrap items-center gap-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari invoice/target..." 
                    class="h-9 bg-[#FFFFFF] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]"
                >
                <select name="status" onchange="this.form.submit()" class="h-9 bg-[#FFFFFF] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1F2419]">
                    <option value="">Semua Status</option>
                    <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Sukses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </form>
        </div>

        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm">
            @if($transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-[10px] text-[#596152] uppercase font-bold border-b border-[#DCD1C2]">
                                <th class="pb-3">Invoice</th>
                                <th class="pb-3">Waktu</th>
                                <th class="pb-3">Produk</th>
                                <th class="pb-3">Target</th>
                                <th class="pb-3">Metode</th>
                                <th class="pb-3">Total</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DCD1C2]/60">
                            @foreach($transactions as $tx)
                                <tr>
                                    <td class="py-3 font-mono font-bold text-[#1F2419]">{{ $tx->invoice_number }}</td>
                                    <td class="py-3 text-[#596152] whitespace-nowrap">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-3 font-bold text-[#1F2419]">{{ $tx->product->name }}</td>
                                    <td class="py-3 text-[#596152]">{{ $tx->target }}</td>
                                    <td class="py-3 uppercase font-semibold text-[#596152]">{{ str_replace('_', ' ', $tx->payment->payment_method ?? 'Wallet') }}</td>
                                    <td class="py-3 font-bold text-[#525A43]">Rp {{ number_format($tx->total, 0, ',', '.') }}</td>
                                    <td class="py-3">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $tx->isSuccess() ? 'bg-emerald-100 text-emerald-800' : ($tx->transaction_status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                            {{ strtoupper($tx->transaction_status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('invoice.show', $tx->invoice_number) }}" class="px-3 py-1 bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white rounded-lg font-bold transition-colors">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pt-4">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-12 text-xs text-[#596152]">
                    <span class="material-symbols-outlined text-4xl mb-1 text-[#76786f]">receipt_long</span>
                    <p>Tidak ada transaksi yang cocok.</p>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
