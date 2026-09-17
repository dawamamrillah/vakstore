@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-10">
    <div class="flex flex-col gap-8">
        
        <!-- User Welcome Banner -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <img src="{{ $user->avatar ?? 'https://lh3.googleusercontent.com/aida/AEtjO1Vz35s7cNNz41844hTRs2yYeWUsQADZmv1cBBT0xB8I9IHvSZy8Uxv9lc45vs94e3sw3tVzmEQ1LWYn7Waj8RUgPoaqAG6S7HCmYlF_PVh_Cia92wuV36EjDpKb7PrI2C1HjOP8fSt1BPfFg_lj-FAnIB-gxcZMCsXUdvZehyrHhhYfndSZ1w4mBzgJK8usJWzeHLuWzxbQb8pUGRgIF85A_mPLuqM870EVWONUFXfUTKImXJKcPcNDvIZJ' }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-2xl object-cover border-2 border-[#525A43]/20 shadow-xs">
                <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-extrabold text-[#1F2419]">{{ $user->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            {{ $user->isAdmin() ? 'Super Admin' : 'Member VIP' }}
                        </span>
                    </div>
                    <span class="text-xs text-[#596152]">{{ $user->email }} • {{ $user->phone }}</span>
                </div>
            </div>

            <!-- Balance Quick Card -->
            <div class="flex items-center gap-4 bg-[#F4EFE6] border border-[#525A43]/20 p-4 rounded-2xl">
                <div class="flex flex-col">
                    <span class="text-[10px] text-[#596152] uppercase font-bold">Saldo Vault Aktif</span>
                    <span class="text-2xl font-extrabold text-[#525A43]">Rp {{ number_format($user->balance, 0, ',', '.') }}</span>
                </div>
                <a href="{{ route('user.wallet') }}" class="px-4 py-2 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">add</span>
                    <span>Top Up</span>
                </a>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs">
                <span class="text-[10px] text-[#596152] uppercase font-bold">Total Transaksi</span>
                <h3 class="text-2xl font-extrabold text-[#1F2419] mt-1">{{ $totalTransactions }}</h3>
            </div>
            <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs">
                <span class="text-[10px] text-emerald-700 uppercase font-bold">Transaksi Berhasil</span>
                <h3 class="text-2xl font-extrabold text-emerald-800 mt-1">{{ $successTransactions }}</h3>
            </div>
            <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs">
                <span class="text-[10px] text-amber-700 uppercase font-bold">Menunggu Bayar</span>
                <h3 class="text-2xl font-extrabold text-amber-800 mt-1">{{ $pendingTransactions }}</h3>
            </div>
            <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-5 shadow-xs">
                <span class="text-[10px] text-red-700 uppercase font-bold">Gagal / Refund</span>
                <h3 class="text-2xl font-extrabold text-red-800 mt-1">{{ $failedTransactions }}</h3>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm flex flex-col gap-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]">
                <h3 class="text-base font-extrabold text-[#1F2419]">Riwayat Transaksi Terakhir</h3>
                <a href="{{ route('user.orders') }}" class="text-xs text-[#525A43] font-bold hover:underline">Lihat Semua</a>
            </div>

            @if($recentTransactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-[10px] text-[#596152] uppercase font-bold border-b border-[#DCD1C2]">
                                <th class="pb-3">Invoice</th>
                                <th class="pb-3">Produk</th>
                                <th class="pb-3">Target</th>
                                <th class="pb-3">Total</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#DCD1C2]/60">
                            @foreach($recentTransactions as $tx)
                                <tr>
                                    <td class="py-3 font-mono font-bold text-[#1F2419]">{{ $tx->invoice_number }}</td>
                                    <td class="py-3 font-semibold text-[#1F2419]">{{ $tx->product->name }}</td>
                                    <td class="py-3 text-[#596152]">{{ $tx->target }}</td>
                                    <td class="py-3 font-bold text-[#525A43]">Rp {{ number_format($tx->total, 0, ',', '.') }}</td>
                                    <td class="py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $tx->isSuccess() ? 'bg-emerald-100 text-emerald-800' : ($tx->transaction_status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                            {{ strtoupper($tx->transaction_status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('invoice.show', $tx->invoice_number) }}" class="px-3 py-1 bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white rounded-lg font-bold transition-colors">
                                            Invoice
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-xs text-[#596152]">
                    <span class="material-symbols-outlined text-3xl mb-1 text-[#76786f]">receipt_long</span>
                    <p>Belum ada transaksi. Mulai top up game favorit Anda!</p>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
