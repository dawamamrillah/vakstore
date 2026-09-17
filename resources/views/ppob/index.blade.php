@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-10">
    
    <!-- Hero / Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10 pb-6 border-b border-[#525A43]/15">
        <div class="flex flex-col">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#525A43]"></span>
                <span class="text-xs font-bold uppercase tracking-widest text-[#525A43]">Pulsa &amp; Paket Data</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-[#1F2419] tracking-tight mt-1">Layanan Pulsa &amp; Data All Operator</h1>
            <p class="text-sm text-[#596152] mt-1.5 max-w-2xl">
                Isi ulang pulsa reguler dan paket kuota data internet seluruh operator resmi (Telkomsel, AXIS, XL Axiata, Tri (3), Smartfren, Indosat Ooredoo IM3, dan by.U) secara instan dan otomatis 24 jam nonstop.
            </p>
        </div>

        <div class="flex items-center gap-2 bg-[#FFFFFF] border border-[#525A43]/15 px-4 py-2 rounded-2xl shadow-xs shrink-0">
            <span class="material-symbols-outlined text-emerald-700">bolt</span>
            <div class="flex flex-col">
                <span class="text-[10px] text-[#596152] uppercase font-bold">Status Server Pulsa &amp; Data</span>
                <span class="text-xs font-bold text-emerald-800">Online &amp; Instant Realtime</span>
            </div>
        </div>
    </div>

    <!-- Category / Services Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($services as $svc)
            <div class="group bg-[#FFFFFF] border border-[#DCD1C2] hover:border-[#525A43] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between">
                <div>
                    <!-- Icon / Banner Preview -->
                    <div class="relative w-full aspect-square bg-[#F4EFE6] border border-[#525A43]/15 rounded-2xl overflow-hidden mb-5 flex items-center justify-center p-3 shadow-inner group-hover:scale-[1.02] transition-transform">
                        @if($svc->image)
                            <img 
                                src="{{ asset($svc->image) }}" 
                                alt="{{ $svc->name }}" 
                                class="w-full h-full object-contain"
                            >
                        @else
                            <span class="material-symbols-outlined text-6xl text-[#525A43]">phone_iphone</span>
                        @endif
                        <span class="absolute top-3 right-3 text-[10px] font-extrabold bg-[#525A43] text-white px-2.5 py-1 rounded-full shadow-xs">
                            {{ $svc->products_count }} Pilihan
                        </span>
                    </div>

                    <!-- Service Details -->
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-[#525A43] uppercase tracking-wider">{{ $svc->publisher }}</span>
                        <h3 class="text-lg font-extrabold text-[#1F2419] group-hover:text-[#525A43] transition-colors mt-0.5">{{ $svc->name }}</h3>
                        <p class="text-xs text-[#596152] mt-2 line-clamp-2 leading-relaxed">
                            {{ $svc->description }}
                        </p>
                    </div>
                </div>

                <!-- CTA Action Button -->
                <div class="mt-6 pt-4 border-t border-[#DCD1C2]/60">
                    <a 
                        href="{{ route('topup.show', $svc->slug) }}" 
                        class="w-full h-11 bg-[#F4EFE6] hover:bg-[#525A43] text-[#1F2419] hover:text-white border border-[#525A43]/20 hover:border-transparent rounded-xl text-xs font-extrabold transition-all duration-200 flex items-center justify-center gap-2 shadow-xs group-hover:shadow"
                    >
                        <span>Isi Pulsa &amp; Data</span>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-4 text-center py-16 bg-[#FFFFFF] rounded-3xl border border-[#DCD1C2]">
                <span class="material-symbols-outlined text-5xl text-[#596152]">phone_iphone</span>
                <p class="text-sm font-bold text-[#1F2419] mt-2">Belum ada operator pulsa &amp; data yang aktif.</p>
            </div>
        @endforelse
    </div>

    <!-- PPOB Guarantee Features -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-12">
        <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-2xl p-5 flex items-start gap-4 shadow-xs">
            <div class="w-10 h-10 rounded-xl bg-[#525A43]/10 text-[#525A43] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">verified_user</span>
            </div>
            <div class="flex flex-col">
                <h4 class="text-sm font-bold text-[#1F2419]">Terhubung Provider Resmi</h4>
                <p class="text-xs text-[#596152] mt-0.5">Semua tagihan terintegrasi langsung dengan Biller PLN, Telkom, PDAM &amp; Operator Seluler.</p>
            </div>
        </div>

        <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-2xl p-5 flex items-start gap-4 shadow-xs">
            <div class="w-10 h-10 rounded-xl bg-[#525A43]/10 text-[#525A43] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">flash_on</span>
            </div>
            <div class="flex flex-col">
                <h4 class="text-sm font-bold text-[#1F2419]">Proses Instan &amp; SN Keluar</h4>
                <p class="text-xs text-[#596152] mt-0.5">Token dan struk pembayaran diterbitkan seketika begitu konfirmasi pembayaran sukses.</p>
            </div>
        </div>

        <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-2xl p-5 flex items-start gap-4 shadow-xs">
            <div class="w-10 h-10 rounded-xl bg-[#525A43]/10 text-[#525A43] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-2xl">support_agent</span>
            </div>
            <div class="flex flex-col">
                <h4 class="text-sm font-bold text-[#1F2419]">Bantuan Siaga 24/7</h4>
                <p class="text-xs text-[#596152] mt-0.5">Tim CS VAKSTORE siap membantu kendala transaksi kapan saja melalui WhatsApp &amp; Tiket.</p>
            </div>
        </div>
    </div>

</div>
@endsection
