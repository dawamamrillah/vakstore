@extends('layouts.app')

@section('content')
<div class="flex flex-col w-full">

    <!-- Sub-Navigation / Breadcrumb & Status Ribbon -->
    <section class="w-full bg-[#f4ebe1]/80 border-b border-[#DCD1C2]">
        <div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs text-[#596152]">
                <a class="hover:text-[#1F2419] transition-colors" href="{{ route('home') }}">Beranda</a>
                <span class="material-symbols-outlined text-xs">chevron_right</span>
                <a class="hover:text-[#1F2419] transition-colors" href="{{ route('topup.index') }}">Top Up Game</a>
                <span class="material-symbols-outlined text-xs">chevron_right</span>
                <span class="text-[#1F2419] font-bold">{{ $game->name }}</span>
            </div>
            <div class="flex items-center gap-2 bg-[#FFFFFF] border border-[#DCD1C2] px-3 py-1 rounded-full shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs text-[#2c3325] font-semibold">Server Provider API Online (Transmisi Instan &lt; 3s)</span>
            </div>
        </div>
    </section>

    <!-- Main Split Architecture -->
    <div class="max-w-content mx-auto w-full px-4 md:px-8 lg:px-12 py-8">
        <form action="{{ route('topup.checkout') }}" method="POST" id="topup-form">
            @csrf
            <input type="hidden" name="product_id" id="selected-product-id" value="">
            <input type="hidden" name="payment_method" id="selected-payment-method" value="qris">
            <input type="hidden" name="voucher_code" id="applied-voucher-code" value="">
            <input type="hidden" name="nickname" id="validated-nickname" value="">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- LEFT FLOW COLUMN (7 Columns) -->
                <div class="lg:col-span-7 flex flex-col gap-6">
                    
                    <!-- Game Hero Banner Card -->
                    <div class="relative overflow-hidden rounded-3xl bg-[#FFFFFF] border border-[#DCD1C2] p-6 shadow-sm flex flex-col sm:flex-row items-center gap-6">
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden shrink-0 shadow-sm bg-[#F4EFE6] border border-[#DCD1C2] relative">
                            <img class="w-full h-full object-cover" src="{{ $game->image }}" alt="{{ $game->name }}">
                            <div class="absolute bottom-0 inset-x-0 bg-[#2c3325]/90 backdrop-blur-xs py-0.5 text-center">
                                <span class="text-[9px] text-[#dee6c8] tracking-wider uppercase font-bold">{{ $game->publisher }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 text-center sm:text-left flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                <span class="bg-[#e2e7d7] text-[#333b28] font-bold px-2 py-0.5 rounded text-[10px] tracking-wider uppercase">VIP PORTAL</span>
                                <span class="bg-[#F4EFE6] border border-[#DCD1C2] text-[#596152] px-2 py-0.5 rounded text-[10px] flex items-center gap-1 font-semibold">
                                    <span class="material-symbols-outlined text-emerald-600 text-xs font-bold">bolt</span> Instant Credit
                                </span>
                            </div>
                            <h1 class="text-2xl font-extrabold text-[#1F2419] tracking-tight">{{ $game->name }}</h1>
                            <p class="text-xs text-[#596152] leading-relaxed">
                                {{ $game->description }}
                            </p>
                        </div>
                    </div>

                    <!-- STEP 1: ACCOUNT CREDENTIALS -->
                    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4">
                        <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]/60">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">1</div>
                                <div class="flex flex-col">
                                    <span class="text-base font-bold text-[#1F2419]">Lengkapi Identitas Akun</span>
                                    <span class="text-xs text-[#596152]">Pastikan format {{ $game->target_field_name }} telah tepat</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                            <div class="{{ $game->has_secondary_target ? 'sm:col-span-6' : 'sm:col-span-9' }} flex flex-col gap-1">
                                <label class="text-xs font-bold text-[#2c3325]" for="input-target">{{ $game->target_field_name }}</label>
                                <div class="relative flex items-center">
                                    <input 
                                        name="target"
                                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-sm font-bold text-[#1F2419] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43] focus:bg-[#FFFFFF] focus:ring-2 focus:ring-[#525A43]/20 transition-all" 
                                        id="input-target" 
                                        placeholder="{{ $game->target_placeholder }}" 
                                        type="text" 
                                        value=""
                                        required
                                    >
                                    <span class="material-symbols-outlined absolute right-3 text-[#878c7f] pointer-events-none text-base">tag</span>
                                </div>
                            </div>

                            @if($game->has_secondary_target)
                                <div class="sm:col-span-3 flex flex-col gap-1">
                                    <label class="text-xs font-bold text-[#2c3325]" for="input-target-secondary">{{ $game->target_secondary_field_name }}</label>
                                    <div class="relative flex items-center">
                                        <input 
                                            name="target_secondary"
                                            class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-sm font-bold text-[#1F2419] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43] focus:bg-[#FFFFFF] focus:ring-2 focus:ring-[#525A43]/20 transition-all" 
                                            id="input-target-secondary" 
                                            placeholder="{{ $game->target_secondary_placeholder }}" 
                                            type="text" 
                                            value=""
                                        >
                                        <span class="material-symbols-outlined absolute right-3 text-[#878c7f] pointer-events-none text-base">pin</span>
                                    </div>
                                </div>
                            @endif

                            <div class="sm:col-span-3">
                                <button type="button" class="w-full h-11 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-xs cursor-pointer" id="btn-validate">
                                    <span class="material-symbols-outlined text-base">person_search</span>
                                    <span id="btn-validate-text">Cek ID</span>
                                </button>
                            </div>
                        </div>

                        <!-- Verified Player Badge Banner -->
                        <div class="bg-[#F4EFE6] border border-[#DCD1C2] rounded-2xl p-3 flex items-center justify-between transition-all" id="verified-box">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-full bg-[#397341]/15 text-[#397341] flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-base">verified</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-[#596152] uppercase font-bold">Pemain Terverifikasi Sistem</span>
                                    <span class="text-xs font-extrabold text-[#1F2419] tracking-wide" id="player-nickname">-</span>
                                </div>
                            </div>
                            <span class="bg-[#397341]/15 text-[#397341] text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-[#397341]/25">Server Sinkron</span>
                        </div>
                    </div>

                    <!-- STEP 2: DENOMINATION SELECTION -->
                    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#DCD1C2]/60">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">2</div>
                                <div class="flex flex-col">
                                    <span class="text-base font-bold text-[#1F2419]">Pilih Nominal &amp; Kategori Item</span>
                                    <span class="text-xs text-[#596152]">Pilih item sesuai kategori pembelian yang Anda butuhkan</span>
                                </div>
                            </div>
                        </div>

                        @php
                            $subCategories = $game->products->pluck('sub_category')->filter()->unique()->values();
                        @endphp

                        @if($subCategories->count() > 1)
                            <!-- Sub-Category Tab Pills -->
                            <div class="flex flex-wrap items-center gap-2 pb-1" id="subcategory-tabs">
                                <button 
                                    type="button" 
                                    class="subcat-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-[#525A43] text-white shadow-xs"
                                    data-category="all"
                                    onclick="filterSubCategory('all', this)"
                                >
                                    Semua Item
                                </button>
                                @foreach($subCategories as $subCat)
                                    <button 
                                        type="button" 
                                        class="subcat-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-[#F6F0E8] border border-[#DCD1C2] text-[#1F2419] hover:bg-[#EAE1D4]"
                                        data-category="{{ $subCat }}"
                                        onclick="filterSubCategory('{{ addslashes($subCat) }}', this)"
                                    >
                                        {{ $subCat }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <!-- Product Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="product-grid">
                            @foreach($game->products as $index => $prod)
                                <div 
                                    class="product-item group relative bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-4 cursor-pointer transition-all duration-200 hover:border-[#525A43] hover:shadow-md flex flex-col justify-between"
                                    data-id="{{ $prod->id }}"
                                    data-name="{{ $prod->name }}"
                                    data-price="{{ $prod->selling_price }}"
                                    data-subcategory="{{ $prod->sub_category }}"
                                    onclick="selectProduct({{ $prod->id }}, '{{ addslashes($prod->name) }}', {{ $prod->selling_price }}, this)"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="w-8 h-8 rounded-xl bg-[#F4EFE6] border border-[#DCD1C2] flex items-center justify-center text-[#525A43]">
                                            @if(str_contains(strtolower($prod->sub_category ?? ''), 'special'))
                                                <span class="material-symbols-outlined text-base text-amber-700">workspace_premium</span>
                                            @elseif(str_contains(strtolower($prod->sub_category ?? ''), 'pack') || str_contains(strtolower($prod->sub_category ?? ''), 'membership') || str_contains(strtolower($prod->sub_category ?? ''), 'pass'))
                                                <span class="material-symbols-outlined text-base text-indigo-700">card_membership</span>
                                            @elseif(str_contains(strtolower($prod->sub_category ?? ''), 'voucher') || str_contains(strtolower($prod->sub_category ?? ''), 'gift'))
                                                <span class="material-symbols-outlined text-base text-rose-700">card_giftcard</span>
                                            @else
                                                <span class="material-symbols-outlined text-base">diamond</span>
                                            @endif
                                        </div>
                                        @if($prod->badge)
                                            <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full {{ $prod->badge === 'FLASH SALE' ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                                {{ $prod->badge }}
                                            </span>
                                        @elseif($prod->sub_category)
                                            <span class="text-[9px] font-bold text-[#5C6454] bg-[#F6F0E8] border border-[#DCD1C2] px-1.5 py-0.5 rounded">
                                                {{ $prod->sub_category }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <h4 class="text-xs font-bold text-[#1F2419] group-hover:text-[#525A43] transition-colors line-clamp-1">{{ $prod->name }}</h4>
                                        <span class="text-sm font-extrabold text-[#525A43] mt-1">Rp {{ number_format($prod->selling_price, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- STEP 3: PAYMENT METHOD -->
                    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4">
                        <div class="flex items-center gap-3 pb-3 border-b border-[#DCD1C2]/60">
                            <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">3</div>
                            <div class="flex flex-col">
                                <span class="text-base font-bold text-[#1F2419]">Metode Pembayaran</span>
                                <span class="text-xs text-[#596152]">Pembayaran instan 24 jam dengan verifikasi otomatis sistem</span>
                            </div>
                        </div>

                        <!-- Exclusive QRIS Dinamis Card -->
                        <div class="border-2 border-[#525A43] bg-[#F4EFE6]/50 rounded-2xl p-4.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
                            <div class="flex items-start sm:items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-[#525A43] text-white flex items-center justify-center shadow-xs shrink-0">
                                    <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-extrabold text-[#1F2419]">QRIS Dinamis 24 Jam</span>
                                        <span class="text-[9px] font-extrabold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded border border-emerald-300">OTOMATIS</span>
                                        <span class="text-[9px] font-extrabold bg-blue-100 text-blue-800 px-2 py-0.5 rounded border border-blue-300">BEBAS BIAYA ADMIN</span>
                                    </div>
                                    <p class="text-xs text-[#596152] mt-0.5">
                                        Dapat dibayar melalui <strong>BCA, Mandiri, BRI, BNI, GoPay, OVO, DANA, ShopeePay, LinkAja</strong> &amp; semua m-Banking berlogo QRIS.
                                    </p>
                                </div>
                            </div>
                            <div class="flex sm:flex-col items-center sm:items-end justify-between border-t sm:border-t-0 pt-2 sm:pt-0 border-[#DCD1C2] shrink-0">
                                <span class="text-[10px] text-[#596152] uppercase font-bold">Total Bayar</span>
                                <span class="text-base font-extrabold text-[#525A43] payment-price-preview">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: VOUCHER CODE -->
                    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4">
                        <div class="flex items-center gap-3 pb-3 border-b border-[#DCD1C2]/60">
                            <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">4</div>
                            <div class="flex flex-col">
                                <span class="text-base font-bold text-[#1F2419]">Gunakan Kode Promo Voucher</span>
                                <span class="text-xs text-[#596152]">Masukkan kupon promo untuk potongan harga spesial</span>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input 
                                    class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 uppercase tracking-wider rounded-xl text-xs font-bold text-[#1F2419] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43]" 
                                    id="voucher-input" 
                                    placeholder="Contoh: TOPUPHEMAT" 
                                    type="text"
                                >
                            </div>
                            <button type="button" class="px-5 h-11 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold transition-colors shadow-xs" id="btn-apply-voucher">
                                Terapkan
                            </button>
                        </div>

                        <div id="voucher-message" class="hidden text-xs font-semibold p-2.5 rounded-xl"></div>
                    </div>

                    <!-- STEP 5: CONTACT INFO -->
                    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4">
                        <div class="flex items-center gap-3 pb-3 border-b border-[#DCD1C2]/60">
                            <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">5</div>
                            <div class="flex flex-col">
                                <span class="text-base font-bold text-[#1F2419]">Informasi Pengiriman Struk / Invoice</span>
                                <span class="text-xs text-[#596152]">Invoice & bukti transaksi akan dicatat pada sistem</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-bold text-[#2c3325]">Nama Pelanggan</label>
                                <input 
                                    name="customer_name" 
                                    id="input-customer-name"
                                    class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                                    type="text" 
                                    value=""
                                    placeholder="Nama pelanggan..."
                                >
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-[#2c3325]">Nomor WhatsApp</label>
                                </div>
                                <input 
                                    name="customer_phone" 
                                    id="input-customer-phone"
                                    class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-bold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                                    type="text" 
                                    value="" 
                                    placeholder="Contoh: 081234567890"
                                    oninput="checkTopupFormValidity()"
                                >
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-[#2c3325]">Email Notifikasi</label>
                                </div>
                                <input 
                                    name="customer_email" 
                                    id="input-customer-email"
                                    class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-bold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                                    type="email" 
                                    value="" 
                                    placeholder="contoh: user@gmail.com"
                                    oninput="checkTopupFormValidity()"
                                >
                            </div>
                        </div>
                        <p class="text-[11px] text-[#596152] mt-0.5">
                            * Masukkan nomor WhatsApp atau alamat Email (salah satu wajib) untuk menerima invoice dan verifikasi jika ada kendala.
                        </p>
                    </div>

                </div>

                <!-- RIGHT ORDER SUMMARY COLUMN (5 Columns) -->
                <div class="lg:col-span-5 sticky top-28">
                    <div class="bg-[#FFFFFF] border border-[#525A43]/20 rounded-3xl p-6 shadow-md flex flex-col gap-5">
                        <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]">
                            <h3 class="text-base font-extrabold text-[#1F2419]">Ringkasan Pembelian</h3>
                            <span class="text-[10px] font-bold bg-[#F4EFE6] text-[#525A43] px-2.5 py-1 rounded-full border border-[#525A43]/15">Private Vault</span>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div class="flex justify-between text-[#596152]">
                                <span>Game / Layanan:</span>
                                <span class="font-bold text-[#1F2419]">{{ $game->name }}</span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Produk Dipilih:</span>
                                <span class="font-bold text-[#1F2419]" id="summary-product-name">-</span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Target Akun:</span>
                                <span class="font-bold text-[#1F2419]" id="summary-target">-</span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Pemain:</span>
                                <span class="font-bold text-emerald-800" id="summary-nickname">-</span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Metode Bayar:</span>
                                <span class="font-bold text-[#1F2419]" id="summary-payment-method">QRIS Dinamis</span>
                            </div>
                            <hr class="border-[#DCD1C2]">
                            <div class="flex justify-between text-[#596152]">
                                <span>Harga Produk:</span>
                                <span class="font-bold text-[#1F2419]" id="summary-price">Rp 0</span>
                            </div>
                            <div class="flex justify-between text-[#397341]">
                                <span>Diskon Voucher:</span>
                                <span class="font-bold" id="summary-discount">- Rp 0</span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Biaya Layanan:</span>
                                <span class="font-bold text-emerald-700">GRATIS</span>
                            </div>
                            <hr class="border-[#DCD1C2]">
                            <div class="flex justify-between items-center text-sm pt-1">
                                <span class="font-bold text-[#1F2419]">Total Pembayaran:</span>
                                <span class="text-xl font-extrabold text-[#525A43]" id="summary-total">Rp 0</span>
                            </div>
                        </div>

                        <!-- Dynamic Checkout Action Button -->
                        <div class="flex flex-col gap-2">
                            <button 
                                type="button" 
                                id="btn-confirm-checkout" 
                                disabled
                                class="w-full py-3.5 rounded-2xl bg-[#8c947e] text-white font-extrabold text-sm transition-all opacity-50 cursor-not-allowed pointer-events-none flex items-center justify-center gap-2"
                            >
                                <span class="material-symbols-outlined text-lg">shopping_cart_checkout</span>
                                <span>Bayar Sekarang &amp; Proses Otomatis</span>
                            </button>

                            <p id="topup-validation-warning" class="text-[11px] text-amber-800 bg-amber-50 border border-amber-200/80 rounded-xl p-2.5 text-center font-medium">
                                ⚠️ Lengkapi User ID, pilih nominal produk, dan salah satu kontak (WhatsApp / Email) untuk mengaktifkan pembayaran.
                            </p>
                        </div>

                        <div class="flex items-center justify-center gap-2 text-[10px] text-[#76786f] pt-1">
                            <span class="material-symbols-outlined text-xs text-emerald-600">verified_user</span>
                            <span>Standar Keamanan Transaksi Digital 256-Bit</span>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
    let currentPrice = 0;
    let currentDiscount = 0;
    let selectedProductId = 0;

    function filterSubCategory(category, btnElement) {
        document.querySelectorAll('.subcat-tab-btn').forEach(btn => {
            btn.className = 'subcat-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-[#F6F0E8] border border-[#DCD1C2] text-[#1F2419] hover:bg-[#EAE1D4]';
        });
        btnElement.className = 'subcat-tab-btn px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-[#525A43] text-white shadow-xs';

        const products = document.querySelectorAll('.product-item');
        products.forEach(p => {
            const sub = p.getAttribute('data-subcategory') || '';
            const isMatch = (category === 'all' || sub === category);
            p.style.display = isMatch ? 'flex' : 'none';
        });
    }

    function updateCalculations() {
        const total = Math.max(0, currentPrice - currentDiscount);
        
        document.getElementById('summary-price').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(currentPrice);
        document.getElementById('summary-discount').innerText = '- Rp ' + new Intl.NumberFormat('id-ID').format(currentDiscount);
        document.getElementById('summary-total').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);

        document.querySelectorAll('.payment-price-preview').forEach(el => {
            el.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        });
    }

    function selectProduct(id, name, price, element) {
        selectedProductId = id;
        currentPrice = price;
        document.getElementById('selected-product-id').value = id;
        document.getElementById('summary-product-name').innerText = name;

        document.querySelectorAll('.product-item').forEach(el => {
            el.classList.remove('border-[#525A43]', 'ring-2', 'ring-[#525A43]/20', 'bg-[#F4EFE6]/40');
            el.classList.add('border-[#DCD1C2]');
        });

        element.classList.remove('border-[#DCD1C2]');
        element.classList.add('border-[#525A43]', 'ring-2', 'ring-[#525A43]/20', 'bg-[#F4EFE6]/40');

        updateCalculations();
        checkTopupFormValidity();
    }

    // Live Cek ID
    document.getElementById('btn-validate').addEventListener('click', async function() {
        const target = document.getElementById('input-target').value;
        const targetSecondary = document.getElementById('input-target-secondary') ? document.getElementById('input-target-secondary').value : '';
        const btnText = document.getElementById('btn-validate-text');
        
        btnText.innerText = 'Memeriksa...';

        try {
            const response = await fetch("{{ route('api.check-id') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    game_slug: "{{ $game->slug }}",
                    user_id: target,
                    zone_id: targetSecondary
                })
            });

            const data = await response.json();
            if (data.status === 'success') {
                document.getElementById('player-nickname').innerText = data.nickname;
                document.getElementById('summary-nickname').innerText = data.nickname;
                document.getElementById('validated-nickname').value = data.nickname;
                document.getElementById('summary-target').innerText = target + (targetSecondary ? ' (' + targetSecondary + ')' : '');
                btnText.innerText = 'Terverifikasi ✓';
            } else {
                alert(data.message || 'ID tidak ditemukan');
                btnText.innerText = 'Cek ID';
            }
        } catch (e) {
            btnText.innerText = 'Cek ID';
        }
    });

    // Voucher Validation
    document.getElementById('btn-apply-voucher').addEventListener('click', async function() {
        const code = document.getElementById('voucher-input').value.trim();
        const msg = document.getElementById('voucher-message');

        if (!code) {
            msg.className = 'text-xs font-semibold p-2.5 rounded-xl bg-red-100 text-red-800';
            msg.innerText = 'Silakan ketik kode voucher terlebih dahulu.';
            msg.classList.remove('hidden');
            return;
        }

        try {
            const response = await fetch("{{ route('api.validate-voucher') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    code: code,
                    subtotal: currentPrice,
                    product_id: selectedProductId
                })
            });

            const data = await response.json();
            if (data.status === 'success') {
                currentDiscount = data.discount;
                document.getElementById('applied-voucher-code').value = data.code;
                msg.className = 'text-xs font-semibold p-2.5 rounded-xl bg-emerald-100 text-emerald-800';
                msg.innerText = '✓ ' + data.message + ' Hemat Rp ' + new Intl.NumberFormat('id-ID').format(data.discount);
                msg.classList.remove('hidden');
                updateCalculations();
            } else {
                currentDiscount = 0;
                document.getElementById('applied-voucher-code').value = '';
                msg.className = 'text-xs font-semibold p-2.5 rounded-xl bg-red-100 text-red-800';
                msg.innerText = '✕ ' + (data.message || 'Voucher tidak valid');
                msg.classList.remove('hidden');
                updateCalculations();
            }
        } catch (e) {
            msg.className = 'text-xs font-semibold p-2.5 rounded-xl bg-red-100 text-red-800';
            msg.innerText = 'Gagal memvalidasi voucher.';
            msg.classList.remove('hidden');
        }
    });

    // Real-Time Form Validation & Checkout Button Activator
    function checkTopupFormValidity() {
        const target = document.getElementById('input-target')?.value.trim();
        const secInput = document.getElementById('input-target-secondary');
        const secondary = secInput ? secInput.value.trim() : 'valid';
        const productId = document.getElementById('selected-product-id')?.value;
        const paymentMethod = document.getElementById('selected-payment-method')?.value;
        const phone = document.getElementById('input-customer-phone')?.value.trim() || '';
        const email = document.getElementById('input-customer-email')?.value.trim() || '';

        // Phone min 9 digits OR valid email
        const phoneDigits = phone.replace(/[^0-9]/g, '');
        const phoneValid = phoneDigits.length >= 9;
        const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        const contactValid = (phoneValid || emailValid);

        const hasSec = (secInput ? Boolean(secondary) : true);
        const isValid = Boolean(target && hasSec && productId && paymentMethod && contactValid);

        const btn = document.getElementById('btn-confirm-checkout');
        const warning = document.getElementById('topup-validation-warning');

        if (btn) {
            if (isValid) {
                btn.removeAttribute('disabled');
                btn.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none', 'bg-[#8c947e]');
                btn.classList.add('bg-[#525A43]', 'hover:bg-[#3B432D]', 'cursor-pointer', 'shadow-md');
                if (warning) warning.classList.add('hidden');
            } else {
                btn.setAttribute('disabled', 'true');
                btn.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none', 'bg-[#8c947e]');
                btn.classList.remove('bg-[#525A43]', 'hover:bg-[#3B432D]', 'cursor-pointer', 'shadow-md');
                if (warning) {
                    warning.classList.remove('hidden');
                    if (!target) {
                        warning.innerText = '⚠️ Silakan masukkan User ID / Akun tujuan Anda.';
                    } else if (secInput && !secondary) {
                        warning.innerText = '⚠️ Silakan masukkan Server / Zone ID game Anda.';
                    } else if (!contactValid) {
                        warning.innerText = '⚠️ Silakan isi Nomor WhatsApp (min 9 digit) atau Email yang valid untuk menerima bukti invoice.';
                    } else if (!productId) {
                        warning.innerText = '⚠️ Silakan pilih nominal item yang ingin dibeli.';
                    } else {
                        warning.innerText = '⚠️ Lengkapi data di atas untuk mengaktifkan tombol bayar.';
                    }
                }
            }
        }
    }

    // Attach listeners
    document.getElementById('input-target')?.addEventListener('input', checkTopupFormValidity);
    document.getElementById('input-target-secondary')?.addEventListener('input', checkTopupFormValidity);

    // Checkout Confirmation
    document.getElementById('btn-confirm-checkout').addEventListener('click', function() {
        const target = document.getElementById('input-target').value;
        if (!target) {
            alert('Mohon masukkan User ID akun Anda.');
            document.getElementById('input-target').focus();
            return;
        }
        document.getElementById('topup-form').submit();
    });

    // Initial setup
    updateCalculations();
    checkTopupFormValidity();
</script>
@endpush
@endsection
