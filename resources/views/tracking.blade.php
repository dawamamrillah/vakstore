@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-10">
    <div class="max-w-3xl mx-auto flex flex-col gap-6">
        
        <!-- Search Box -->
        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-[#525A43] text-white flex items-center justify-center font-bold">
                    <span class="material-symbols-outlined text-xl">search_check</span>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-[#1F2419]">Pelacakan Status Pesanan & Invoice</h1>
                    <p class="text-xs text-[#596152]">Masukkan Nomor Invoice Transaksi (Contoh: TRX-20260908-XXXXXX) atau Nomor HP</p>
                </div>
            </div>

            <form action="{{ route('tracking') }}" method="GET" class="mt-4 flex gap-2">
                <div class="relative flex-1">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ $search }}" 
                        class="w-full h-12 bg-[#fbf8f4] border border-[#DCD1C2] px-4 rounded-xl text-sm font-bold text-[#1F2419] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43]" 
                        placeholder="TRX-..." 
                        required
                    >
                </div>
                <button type="submit" class="px-6 h-12 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1">
                    <span>Lacak</span>
                    <span class="material-symbols-outlined text-base">arrow_forward</span>
                </button>
            </form>
        </div>

        <!-- Result Section -->
        @if($transaction)
            <div class="bg-[#FFFFFF] border border-[#525A43]/20 rounded-3xl p-6 lg:p-8 shadow-md flex flex-col gap-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#DCD1C2]">
                    <div class="flex flex-col">
                        <span class="text-[10px] text-[#596152] uppercase font-bold">Nomor Invoice</span>
                        <span class="text-lg font-extrabold text-[#1F2419] tracking-wider">{{ $transaction->invoice_number }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($transaction->transaction_status === 'success')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm font-bold">check_circle</span>
                                Sukses Terkirim
                            </span>
                        @elseif($transaction->transaction_status === 'processing')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-sky-100 text-sky-800 border border-sky-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm font-bold animate-spin">sync</span>
                                Sedang Diproses Provider
                            </span>
                        @elseif($transaction->transaction_status === 'pending')
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm font-bold">schedule</span>
                                Menunggu Pembayaran
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm font-bold">cancel</span>
                                {{ ucfirst($transaction->transaction_status) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div class="flex flex-col gap-1 p-3 rounded-xl bg-[#F4EFE6]">
                        <span class="text-[10px] text-[#596152] uppercase font-bold">Produk / Layanan</span>
                        <span class="font-extrabold text-[#1F2419] text-sm">{{ $transaction->product->name }}</span>
                    </div>
                    <div class="flex flex-col gap-1 p-3 rounded-xl bg-[#F4EFE6]">
                        <span class="text-[10px] text-[#596152] uppercase font-bold">Target Akun / ID</span>
                        <span class="font-extrabold text-[#1F2419] text-sm">{{ $transaction->target }} {{ $transaction->target_secondary ? '('.$transaction->target_secondary.')' : '' }}</span>
                    </div>
                    <div class="flex flex-col gap-1 p-3 rounded-xl bg-[#F4EFE6]">
                        <span class="text-[10px] text-[#596152] uppercase font-bold">Total Pembayaran</span>
                        <span class="font-extrabold text-[#525A43] text-sm">Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex flex-col gap-1 p-3 rounded-xl bg-[#F4EFE6]">
                        <span class="text-[10px] text-[#596152] uppercase font-bold">Serial Number / SN</span>
                        <span class="font-mono font-bold text-emerald-900 text-sm select-all">{{ $transaction->serial_number ?? 'Sedang digenerate provider...' }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <span class="text-xs text-[#596152]">Waktu Transaksi: {{ $transaction->created_at->format('d M Y, H:i') }} WIB</span>
                    <a href="{{ route('invoice.show', $transaction->invoice_number) }}" class="px-4 py-2 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold transition-all shadow-xs flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">receipt</span>
                        <span>Lihat Invoice Lengkap</span>
                    </a>
                </div>
            </div>
        @elseif(!empty($search))
            <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-8 text-center text-[#596152]">
                <span class="material-symbols-outlined text-4xl text-amber-600 mb-2">search_off</span>
                <h3 class="text-base font-bold text-[#1F2419]">Transaksi Tidak Ditemukan</h3>
                <p class="text-xs mt-1">Pastikan Anda memasukkan nomor invoice yang tepat seperti TRX-2026...</p>
            </div>
        @endif

    </div>
</div>
@endsection
