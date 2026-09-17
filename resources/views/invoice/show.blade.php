@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-10">
    <div class="max-w-2xl mx-auto flex flex-col gap-6">
        
        <!-- Action Buttons -->
        <div class="flex items-center justify-between no-print">
            <a href="{{ route('home') }}" class="px-4 py-2 rounded-xl bg-[#FFFFFF] border border-[#DCD1C2] hover:bg-[#F4EFE6] text-xs font-bold text-[#1F2419] transition-all flex items-center gap-1 shadow-xs">
                <span class="material-symbols-outlined text-base">arrow_back</span>
                <span>Kembali ke Beranda</span>
            </a>

            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-[#FFFFFF] border border-[#DCD1C2] hover:bg-[#F4EFE6] text-xs font-bold text-[#1F2419] transition-all flex items-center gap-1 shadow-xs">
                    <span class="material-symbols-outlined text-base">print</span>
                    <span>Cetak Struk</span>
                </button>
            </div>
        </div>

        <!-- Pending Mock Payment Action Banner -->
        @if($transaction->payment_status === 'pending')
            <div class="bg-amber-50 border border-amber-300 rounded-3xl p-6 shadow-sm flex flex-col gap-4 no-print">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-amber-700 text-3xl">schedule</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-amber-900">Menunggu Pembayaran: {{ strtoupper(str_replace('_', ' ', $transaction->payment->payment_method ?? 'QRIS')) }}</h3>
                            <p class="text-xs text-amber-800">Selesaikan pembayaran untuk memicu auto-delivery item.</p>
                        </div>
                    </div>
                    <span class="text-base font-extrabold text-amber-900">Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
                </div>

                @if($transaction->payment?->payment_method === 'qris')
                    <div class="bg-white p-4 rounded-2xl border border-amber-200 flex flex-col items-center text-center">
                        <span class="text-xs font-bold text-[#1F2419] mb-2">Scan Kode QRIS Pembayaran Resmi</span>
                        <div class="w-48 h-48 bg-slate-900 text-white flex flex-col items-center justify-center rounded-xl p-2 font-mono text-[10px] select-all break-all">
                            <span class="material-symbols-outlined text-4xl mb-1">qr_code_2</span>
                            <span>QRIS_PAYLOAD_VAKSTORE</span>
                        </div>
                    </div>
                @elseif($transaction->payment?->pay_code)
                    <div class="bg-white p-4 rounded-2xl border border-amber-200 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] uppercase font-bold text-slate-500">Nomor Rekening / Virtual Account</span>
                            <span class="text-lg font-mono font-extrabold text-slate-900 select-all">{{ $transaction->payment->pay_code }}</span>
                        </div>
                        <button onclick="navigator.clipboard.writeText('{{ $transaction->payment->pay_code }}'); this.innerText='Tersalin!';" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-bold transition-all">Salin</button>
                    </div>
                @endif

                <!-- Payment Confirmation Action -->
                <form action="{{ route('invoice.simulate-pay', $transaction->invoice_number) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3.5 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-extrabold shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-base">verified</span>
                        <span>Konfirmasi Pembayaran Otomatis (Proses Instan)</span>
                    </button>
                </form>
            </div>
        @endif

        <!-- Luxury Printable Invoice Card -->
        <div class="bg-[#FFFFFF] border border-[#525A43]/20 rounded-3xl p-8 lg:p-10 shadow-lg flex flex-col gap-6" id="invoice-card">
            
            <!-- Invoice Header -->
            <div class="flex items-start justify-between pb-6 border-b border-[#DCD1C2]">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="VAKSTORE Logo" class="h-12 w-auto object-contain shrink-0">
                    <div class="flex flex-col">
                        <span class="font-extrabold text-2xl tracking-tight text-[#1F2419]">VAK<span class="text-[#525A43]">STORE</span></span>
                        <span class="text-[10px] text-[#525A43] tracking-widest uppercase font-bold">Official Digital Receipt</span>
                    </div>
                </div>

                <div class="text-right">
                    <span class="text-[10px] text-[#596152] uppercase font-bold">No. Invoice</span>
                    <h2 class="text-base font-extrabold text-[#1F2419] tracking-wider">{{ $transaction->invoice_number }}</h2>
                    <span class="text-[11px] text-[#596152]">{{ $transaction->created_at->format('d M Y, H:i') }} WIB</span>
                </div>
            </div>

            <!-- Transaction Status Ribbon -->
            <div class="flex flex-wrap items-center justify-between gap-2 p-4 rounded-2xl {{ $transaction->isSuccess() ? 'bg-emerald-50 border border-emerald-200 text-emerald-950' : ($transaction->transaction_status === 'pending' ? 'bg-amber-50 border border-amber-200 text-amber-950' : 'bg-red-50 border border-red-200 text-red-950') }}">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-2xl {{ $transaction->isSuccess() ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $transaction->isSuccess() ? 'task_alt' : 'hourglass_top' }}
                    </span>
                    <div class="flex flex-col">
                        <span class="text-xs font-extrabold uppercase">
                            {{ $transaction->isSuccess() ? 'TRANSAKSI SELESAI (SUCCESS)' : 'STATUS: ' . strtoupper($transaction->transaction_status) }}
                        </span>
                        <span class="text-[11px] text-slate-600">
                            {{ $transaction->isSuccess() ? 'Produk telah berhasil ditransmisikan ke akun target.' : 'Sistem sedang memproses antrian gateway.' }}
                        </span>
                    </div>
                </div>

                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $transaction->isPaid() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $transaction->isPaid() ? 'LUNAS' : 'BELUM DIBAYAR' }}
                </span>
            </div>

            <!-- Serial Number Box (SN / Token Listrik) -->
            @if($transaction->serial_number)
                <div class="p-4 rounded-2xl bg-[#F4EFE6] border border-[#525A43]/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex flex-col">
                        <span class="text-[10px] text-[#525A43] uppercase font-extrabold tracking-wider">KODE SERIAL NUMBER / TOKEN RESMI (SN)</span>
                        <span class="text-base font-mono font-extrabold text-[#1F2419] tracking-wider select-all">{{ $transaction->serial_number }}</span>
                    </div>
                    <button onclick="navigator.clipboard.writeText('{{ $transaction->serial_number }}'); this.innerText='Tersalin!';" class="px-3 py-1.5 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold transition-all shadow-xs shrink-0">
                        Salin SN
                    </button>
                </div>
            @endif

            <!-- Customer & Target Data -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                <div class="flex flex-col">
                    <span class="text-[10px] text-[#596152] uppercase font-bold">Nama Pembeli</span>
                    <span class="font-bold text-[#1F2419] mt-0.5">{{ $transaction->customer_name ?? 'Guest User' }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] text-[#596152] uppercase font-bold">Target Akun / ID</span>
                    <span class="font-bold text-[#1F2419] mt-0.5">{{ $transaction->target }} {{ $transaction->target_secondary ? '('.$transaction->target_secondary.')' : '' }}</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] text-[#596152] uppercase font-bold">Metode Pembayaran</span>
                    <span class="font-bold text-[#1F2419] mt-0.5">{{ strtoupper(str_replace('_', ' ', $transaction->payment->payment_method ?? 'Wallet')) }}</span>
                </div>
            </div>

            <!-- Itemized Pricing Table -->
            <div class="border-t border-[#DCD1C2] pt-4">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-[10px] text-[#596152] uppercase font-bold border-b border-[#DCD1C2]">
                            <th class="text-left pb-2">Deskripsi Produk</th>
                            <th class="text-right pb-2">Harga</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#DCD1C2]/60">
                        <tr>
                            <td class="py-3 font-bold text-[#1F2419]">{{ $transaction->product->name }}</td>
                            <td class="py-3 text-right font-extrabold text-[#1F2419]">Rp {{ number_format($transaction->selling_price, 0, ',', '.') }}</td>
                        </tr>
                        @if($transaction->discount > 0)
                            <tr>
                                <td class="py-2 text-[#397341] font-semibold">Diskon Voucher ({{ $transaction->voucher_code }})</td>
                                <td class="py-2 text-right text-[#397341] font-bold">- Rp {{ number_format($transaction->discount, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                        @if($transaction->admin_fee > 0)
                            <tr>
                                <td class="py-2 text-[#596152]">Biaya Admin Layanan</td>
                                <td class="py-2 text-right font-semibold text-[#1F2419]">Rp {{ number_format($transaction->admin_fee, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-[#1F2419]">
                            <td class="pt-3 text-sm font-extrabold text-[#1F2419]">Total Pembayaran</td>
                            <td class="pt-3 text-right text-lg font-extrabold text-[#525A43]">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer note -->
            <div class="pt-4 border-t border-[#DCD1C2] text-center text-[10px] text-[#76786f]">
                <p>Terima kasih telah bertransaksi di VAKSTORE. Simpan invoice ini sebagai bukti sah pembelian digital.</p>
                <p class="mt-0.5">Layanan bantuan 24 Jam via WhatsApp: +62 812-3456-7890</p>
            </div>

        </div>

    </div>
</div>

<style>
@media print {
    .no-print, header, footer {
        display: none !important;
    }
    main {
        padding-top: 0 !important;
    }
    body {
        background-color: #ffffff !important;
    }
    #invoice-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
}
</style>
@endsection
