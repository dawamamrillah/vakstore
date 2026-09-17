@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto space-y-6 pb-12">
    
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.transactions') }}" class="px-4 py-2 rounded-xl bg-[#FFFFFF] border border-[#DCD1C2] hover:bg-[#F4EFE6] text-xs font-bold text-[#1A2016] transition-all flex items-center gap-1 shadow-xs">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            <span>Kembali ke Daftar Transaksi</span>
        </a>

        <div class="flex items-center gap-2">
            @if($transaction->payment_status === 'paid' && $transaction->transaction_status !== 'refunded')
                <form action="{{ route('admin.transactions.refund', $transaction->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin melakukan refund untuk transaksi ini? Dana akan dikembalikan ke Saldo Vault pengguna.')">
                    @csrf
                    <input type="hidden" name="reason" value="Refund manual oleh Super Administrator">
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">undo</span>
                        <span>Eksekusi Refund Transaksi</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Detailed Snapshot Card -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-8 shadow-md flex flex-col gap-6">
        <div class="flex items-start justify-between pb-6 border-b border-[#DCD1C2]">
            <div>
                <span class="text-[10px] uppercase font-bold text-[#5C6454]">Nomor Invoice</span>
                <h2 class="text-xl font-extrabold text-[#1A2016]">{{ $transaction->invoice_number }}</h2>
                <span class="text-xs text-[#5C6454]">{{ $transaction->created_at->format('d F Y, H:i:s') }} WIB</span>
            </div>

            <div class="text-right">
                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $transaction->isSuccess() ? 'bg-emerald-100 text-emerald-800' : ($transaction->transaction_status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                    STATUS: {{ strtoupper($transaction->transaction_status) }}
                </span>
            </div>
        </div>

        <!-- Financial Breakdown -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-[#F6F0E8] p-4 rounded-2xl">
            <div>
                <span class="text-[10px] text-[#5C6454] uppercase font-bold">Harga Modal</span>
                <p class="text-sm font-extrabold text-[#1A2016]">Rp {{ number_format($transaction->cost_price, 0, ',', '.') }}</p>
            </div>
            <div>
                <span class="text-[10px] text-[#5C6454] uppercase font-bold">Harga Jual</span>
                <p class="text-sm font-extrabold text-[#1A2016]">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</p>
            </div>
            <div>
                <span class="text-[10px] text-emerald-800 uppercase font-bold">Diskon</span>
                <p class="text-sm font-extrabold text-emerald-800">- Rp {{ number_format($transaction->discount, 0, ',', '.') }}</p>
            </div>
            <div>
                <span class="text-[10px] text-emerald-800 uppercase font-bold">Laba Bersih</span>
                <p class="text-base font-extrabold text-emerald-800">+ Rp {{ number_format($transaction->profit, 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Technical & Delivery Info -->
        <div class="space-y-2 text-xs">
            <h4 class="font-extrabold text-sm text-[#1A2016] border-b border-[#DCD1C2] pb-2">Data Teknis & Integrasi Gateway</h4>
            
            <div class="flex justify-between py-1.5 border-b border-[#DCD1C2]/60">
                <span class="text-[#5C6454]">Produk / SKU:</span>
                <span class="font-bold text-[#1A2016]">{{ $transaction->product->name }} ({{ $transaction->product->sku }})</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-[#DCD1C2]/60">
                <span class="text-[#5C6454]">Target Akun:</span>
                <span class="font-bold text-[#1A2016]">{{ $transaction->target }} {{ $transaction->target_secondary ? '('.$transaction->target_secondary.')' : '' }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-[#DCD1C2]/60">
                <span class="text-[#5C6454]">Nickname Validasi:</span>
                <span class="font-bold text-emerald-800">{{ $transaction->nickname ?? '-' }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-[#DCD1C2]/60">
                <span class="text-[#5C6454]">Provider Reference:</span>
                <span class="font-mono text-[#1A2016]">{{ $transaction->provider_reference ?? '-' }}</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-[#DCD1C2]/60">
                <span class="text-[#5C6454]">Serial Number (SN):</span>
                <span class="font-mono font-bold text-emerald-900">{{ $transaction->serial_number ?? '-' }}</span>
            </div>
            @if($transaction->failure_reason)
                <div class="flex justify-between py-1.5 text-red-700 font-semibold">
                    <span>Catatan Masalah:</span>
                    <span>{{ $transaction->failure_reason }}</span>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
