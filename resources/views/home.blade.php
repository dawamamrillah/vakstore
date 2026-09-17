@extends('layouts.app')

@section('content')
<div class="flex flex-col w-full">

    <!-- Subtle Ambient Glow Orbs behind Hero -->
    <div class="relative w-full overflow-hidden">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[720px] h-[380px] bg-[#9CA28B]/20 rounded-full blur-[120px] pointer-events-none -z-10"></div>
        <div class="absolute top-80 -right-40 w-[420px] h-[420px] bg-[#DFD2C2]/50 rounded-full blur-[100px] pointer-events-none -z-10"></div>

        <!-- Official Poster Banner Showcase (4 Rounded Corners) -->
        <section class="max-w-content mx-auto px-4 md:px-8 lg:px-12 pt-6 pb-4">
            <div class="w-full rounded-3xl overflow-hidden shadow-lg border border-[#525A43]/20 bg-[#F4EFE6] transition-all hover:shadow-xl">
                <img 
                    src="{{ asset('images/poster12.png') }}?v={{ file_exists(public_path('images/poster12.png')) ? filemtime(public_path('images/poster12.png')) : time() }}" 
                    alt="VAKSTORE Official Banner Promo" 
                    class="w-full h-auto max-h-[460px] md:max-h-[520px] object-cover object-center rounded-3xl block shadow-sm"
                >
            </div>
        </section>

        <!-- Interactive Real-Time Search Bar Section -->
        <section class="max-w-content mx-auto px-4 md:px-8 lg:px-12 pb-8 pt-2" id="search-section">
            <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-3xl p-5 md:p-6 shadow-sm flex flex-col gap-3">
                <form onsubmit="submitHomeSearch(event)" class="relative flex items-center bg-[#F4EFE6] rounded-2xl border border-[#525A43]/25 shadow-inner focus-within:border-[#525A43] focus-within:bg-white transition-all p-1.5">
                    <span class="material-symbols-outlined text-[#525A43] pl-3 text-2xl">search</span>
                    <input 
                        name="search"
                        class="w-full bg-transparent py-2.5 px-3 text-sm font-semibold text-[#1F2419] placeholder:text-[#76786f] focus:outline-none border-0 focus:ring-0" 
                        id="main-search-input" 
                        placeholder="Ketik nama game, pulsa, token PLN, atau nomor invoice pesanan..." 
                        type="text"
                        autocomplete="off"
                        oninput="handleHomeSearch(this.value)"
                    >
                    <button 
                        type="button" 
                        onclick="clearHomeSearch()" 
                        id="search-clear-btn" 
                        class="hidden px-2 text-[#76786f] hover:text-[#1F2419] focus:outline-none"
                    >
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                    <button 
                        type="submit" 
                        class="px-6 py-2.5 bg-[#525A43] hover:bg-[#3B432D] text-white text-xs font-extrabold tracking-wider uppercase rounded-xl transition-all flex items-center gap-1.5 shadow-xs cursor-pointer shrink-0 ml-1"
                    >
                        <span>Cari</span>
                        <span class="material-symbols-outlined text-base">arrow_forward</span>
                    </button>
                </form>

                <!-- Search Feedback Status -->
                <div id="search-feedback" class="hidden text-xs font-semibold text-[#525A43] px-2 py-1 bg-[#F4EFE6] rounded-xl border border-[#525A43]/15"></div>

                <!-- Popular Keywords Hot Tags -->
                <div class="flex flex-wrap items-center gap-2 pt-1 text-[#46483f]">
                    <span class="text-xs text-[#525A43] uppercase tracking-wider font-bold shrink-0">Populer:</span>
                    <button type="button" onclick="searchByTag('Mobile Legends')" class="px-3 py-1 bg-[#F4EFE6] border border-[#525A43]/15 rounded-xl text-xs text-[#2A3022] hover:text-white hover:bg-[#525A43] transition-colors font-medium cursor-pointer">Mobile Legends</button>
                    <button type="button" onclick="searchByTag('Free Fire')" class="px-3 py-1 bg-[#F4EFE6] border border-[#525A43]/15 rounded-xl text-xs text-[#2A3022] hover:text-white hover:bg-[#525A43] transition-colors font-medium cursor-pointer">Free Fire</button>
                    <button type="button" onclick="searchByTag('PUBG Mobile')" class="px-3 py-1 bg-[#F4EFE6] border border-[#525A43]/15 rounded-xl text-xs text-[#2A3022] hover:text-white hover:bg-[#525A43] transition-colors font-medium cursor-pointer">PUBG Mobile</button>
                    <button type="button" onclick="searchByTag('Valorant')" class="px-3 py-1 bg-[#F4EFE6] border border-[#525A43]/15 rounded-xl text-xs text-[#2A3022] hover:text-white hover:bg-[#525A43] transition-colors font-medium cursor-pointer">Valorant</button>
                    <button type="button" onclick="searchByTag('Honor of Kings')" class="px-3 py-1 bg-[#F4EFE6] border border-[#525A43]/15 rounded-xl text-xs text-[#2A3022] hover:text-white hover:bg-[#525A43] transition-colors font-medium cursor-pointer">Honor of Kings</button>
                    <button type="button" onclick="searchByTag('PLN')" class="px-3 py-1 bg-[#F4EFE6] border border-[#525A43]/15 rounded-xl text-xs text-[#2A3022] hover:text-white hover:bg-[#525A43] transition-colors font-medium cursor-pointer">PLN</button>
                    <button type="button" onclick="searchByTag('PDAM')" class="px-3 py-1 bg-[#F4EFE6] border border-[#525A43]/15 rounded-xl text-xs text-[#2A3022] hover:text-white hover:bg-[#525A43] transition-colors font-medium cursor-pointer">Tagihan PDAM</button>
                    <button type="button" onclick="searchByTag('Pulsa')" class="px-3 py-1 bg-[#F4EFE6] border border-[#525A43]/15 rounded-xl text-xs text-[#2A3022] hover:text-white hover:bg-[#525A43] transition-colors font-medium cursor-pointer">Pulsa &amp; Data</button>
                </div>
            </div>
        </section>
    </div>

    <!-- GAME TOP UP CATALOG SECTION -->
    <section class="max-w-content mx-auto w-full px-4 md:px-8 lg:px-12 py-8" id="game-section">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-[#525A43]">
                    <span class="material-symbols-outlined text-lg">sports_esports</span>
                    <span class="text-xs font-bold uppercase tracking-widest">Katalog Game Populer</span>
                </div>
                <h2 class="text-2xl lg:text-3xl font-extrabold text-[#1F2419] tracking-tight">Top Up Game Terpopuler</h2>
                <p class="text-xs text-[#596152]">Pilihan game terfavorit dengan harga terbaik dan pengiriman instan 1-3 detik</p>
            </div>
            <a href="{{ route('topup.index') }}" class="px-4 py-2 rounded-xl bg-[#FFFFFF] border border-[#525A43]/15 hover:bg-[#F4EFE6] text-xs font-bold text-[#1F2419] transition-colors shadow-xs flex items-center gap-1">
                <span>Lihat Semua Game</span>
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="game-catalog-grid">
            @foreach($gameServices as $game)
                <a 
                    href="{{ route('topup.show', $game->slug) }}" 
                    class="game-catalog-item group relative bg-[#FFFFFF] border border-[#525A43]/15 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-[#525A43] transition-all flex flex-col items-center text-center"
                    data-name="{{ strtolower($game->name) }}"
                    data-publisher="{{ strtolower($game->publisher) }}"
                >
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden mb-3 bg-[#F4EFE6] border border-[#525A43]/15 shadow-xs relative group-hover:scale-105 transition-transform duration-300 flex items-center justify-center">
                        @if($game->image)
                            <img src="{{ $game->image }}" alt="{{ $game->name }}" class="w-full h-full object-cover">
                        @else
                            <span class="material-symbols-outlined text-4xl text-[#525A43]">sports_esports</span>
                        @endif
                    </div>
                    <span class="text-[10px] uppercase tracking-wider text-[#525A43] font-bold mb-0.5">{{ $game->publisher }}</span>
                    <h3 class="text-sm font-bold text-[#1F2419] group-hover:text-[#525A43] transition-colors leading-snug line-clamp-1">{{ $game->name }}</h3>
                    <div class="mt-2 text-[11px] text-[#596152] flex items-center gap-1 bg-[#F4EFE6] px-2.5 py-0.5 rounded-full border border-[#525A43]/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Mulai Rp {{ number_format($game->products->min('selling_price') ?? 1000, 0, ',', '.') }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- PULSA & OPERATOR SECTION -->
    <section class="max-w-content mx-auto w-full px-4 md:px-8 lg:px-12 py-8" id="pulsa-section">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-[#525A43]">
                    <span class="material-symbols-outlined text-lg">phone_iphone</span>
                    <span class="text-xs font-bold uppercase tracking-widest">Pulsa &amp; Kuota Data</span>
                </div>
                <h2 class="text-2xl lg:text-3xl font-extrabold text-[#1F2419] tracking-tight">Pulsa &amp; Paket Data All Operator</h2>
                <p class="text-xs text-[#596152]">Isi ulang pulsa &amp; kuota data Telkomsel, Indosat, XL, AXIS, Tri, Smartfren, by.U otomatis 24 Jam</p>
            </div>
            <a href="{{ route('ppob.index') }}" class="px-4 py-2 rounded-xl bg-[#FFFFFF] border border-[#525A43]/15 hover:bg-[#F4EFE6] text-xs font-bold text-[#1F2419] transition-colors shadow-xs flex items-center gap-1">
                <span>Semua Pulsa</span>
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="pulsa-catalog-grid">
            @foreach($pulsaServices as $operator)
                <a 
                    href="{{ route('topup.show', $operator->slug) }}" 
                    class="pulsa-catalog-item bg-[#FFFFFF] border border-[#525A43]/15 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-[#525A43] transition-all flex flex-col justify-between group"
                    data-name="{{ strtolower($operator->name) }}"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-12 h-12 rounded-xl bg-[#F4EFE6] border border-[#525A43]/20 text-[#525A43] flex items-center justify-center font-bold">
                            <span class="material-symbols-outlined text-2xl">phone_iphone</span>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">Prabayar</span>
                    </div>
                    <div class="flex flex-col">
                        <h3 class="text-base font-bold text-[#1F2419] group-hover:text-[#525A43] transition-colors">{{ $operator->name }}</h3>
                        <p class="text-xs text-[#596152] mt-0.5">Mulai Rp {{ number_format($operator->products->min('selling_price') ?? 5000, 0, ',', '.') }}</p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#525A43]/10 flex items-center justify-between text-xs font-semibold text-[#525A43]">
                        <span>Isi Pulsa</span>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <!-- PASCABAYAR BILL PAYMENT SECTION -->
    <section class="max-w-content mx-auto w-full px-4 md:px-8 lg:px-12 py-8" id="pasca-section">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-[#525A43]">
                    <span class="material-symbols-outlined text-lg">receipt_long</span>
                    <span class="text-xs font-bold uppercase tracking-widest">Tagihan Pembayaran Pascabayar</span>
                </div>
                <h2 class="text-2xl lg:text-3xl font-extrabold text-[#1F2419] tracking-tight">Cek &amp; Bayar Tagihan Bulanan</h2>
                <p class="text-xs text-[#596152]">Bayar tagihan air PDAM 50 daerah se-Indonesia dan tagihan internet IndiHome/Biznet resmi</p>
            </div>
            <a href="{{ route('ppob.index') }}" class="px-4 py-2 rounded-xl bg-[#FFFFFF] border border-[#525A43]/15 hover:bg-[#F4EFE6] text-xs font-bold text-[#1F2419] transition-colors shadow-xs flex items-center gap-1">
                <span>Semua Tagihan</span>
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="pasca-catalog-grid">
            <!-- PDAM Nusantara -->
            <a 
                href="{{ route('ppob.show', 'pdam-nusantara') }}" 
                class="pasca-catalog-item bg-[#FFFFFF] border border-[#525A43]/15 rounded-3xl p-6 shadow-sm hover:shadow-md hover:border-[#525A43] transition-all flex flex-col justify-between group"
                data-name="pdam air pam jaya palyja aetra surabaya bogor nusantara 50 daerah"
            >
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-cyan-50 border border-cyan-200 text-cyan-800 flex items-center justify-center font-bold shadow-xs">
                            <span class="material-symbols-outlined text-2xl">water_drop</span>
                        </div>
                        <span class="text-[10px] font-bold text-cyan-800 bg-cyan-50 border border-cyan-200 px-2.5 py-1 rounded-full">50 Wilayah</span>
                    </div>
                    <h3 class="text-lg font-bold text-[#1F2419] group-hover:text-[#525A43] transition-colors">Tagihan Air PDAM</h3>
                    <p class="text-xs text-[#596152] mt-1 leading-relaxed">Bayar tagihan air PDAM PAM JAYA, Palyja, Aetra, Surabaya, Bogor, Tangerang, Bali, dan 50 kota/kabupaten.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-[#525A43]/10 flex items-center justify-between text-xs font-bold text-[#525A43]">
                    <span>Pilih Wilayah PDAM</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </div>
            </a>

            <!-- Internet & IndiHome -->
            <a 
                href="{{ route('ppob.show', 'telkom-indihome') }}" 
                class="pasca-catalog-item bg-[#FFFFFF] border border-[#525A43]/15 rounded-3xl p-6 shadow-sm hover:shadow-md hover:border-[#525A43] transition-all flex flex-col justify-between group"
                data-name="indihome telkom biznet first media myrepublic cbn oxygen internet wifi tv kabel"
            >
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-200 text-red-800 flex items-center justify-center font-bold shadow-xs">
                            <span class="material-symbols-outlined text-2xl">router</span>
                        </div>
                        <span class="text-[10px] font-bold text-red-800 bg-red-50 border border-red-200 px-2.5 py-1 rounded-full">9 Provider</span>
                    </div>
                    <h3 class="text-lg font-bold text-[#1F2419] group-hover:text-[#525A43] transition-colors">Internet &amp; TV Kabel</h3>
                    <p class="text-xs text-[#596152] mt-1 leading-relaxed">Bayar tagihan IndiHome Speedy, Telkom PSTN, Biznet Home, First Media, MyRepublic, CBN, Oxygen.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-[#525A43]/10 flex items-center justify-between text-xs font-bold text-[#525A43]">
                    <span>Cek Tagihan Internet</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </div>
            </a>
        </div>
    </section>

    <!-- PLN TOKEN LISTRIK & TAGIHAN SECTION -->
    <section class="max-w-content mx-auto w-full px-4 md:px-8 lg:px-12 py-6 pb-12" id="pln-section">
        <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-3xl p-6 lg:p-8 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <div class="lg:col-span-8 flex flex-col gap-3">
                    <div class="inline-flex items-center gap-2 self-start px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold">
                        <span class="material-symbols-outlined text-base">bolt</span>
                        <span>Layanan Listrik PLN 24 Jam</span>
                    </div>
                    <h2 class="text-2xl font-extrabold text-[#1F2419]">Listrik PLN (Token Prabayar &amp; Tagihan Pascabayar)</h2>
                    <p class="text-xs md:text-sm text-[#596152] leading-relaxed max-w-xl">
                        Beli token listrik PLN prabayar resmi atau cek dan bayar tagihan rekening listrik bulanan secara instan, otomatis terverifikasi 24 jam nonstop.
                    </p>
                </div>
                <div class="lg:col-span-4 flex flex-col sm:flex-row lg:flex-col gap-3 justify-end">
                    <a href="{{ route('ppob.show', 'pln') }}" class="px-6 py-3.5 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-sm font-extrabold flex items-center justify-center gap-2 shadow-sm transition-colors text-center">
                        <span class="material-symbols-outlined text-lg">bolt</span>
                        <span>Buka Layanan PLN</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- WHY CHOOSE US / LUXURY ATTRIBUTES -->
    <section class="max-w-content mx-auto w-full px-4 md:px-8 lg:px-12 py-12">
        <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-3xl p-8 lg:p-12 shadow-sm">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="text-xs font-bold uppercase tracking-widest text-[#525A43]">Standar Keunggulan VAKSTORE</span>
                <h2 class="text-2xl lg:text-3xl font-extrabold text-[#1F2419] tracking-tight mt-1">Integritas Transaksi &amp; Kecepatan Tanpa Kompromi</h2>
                <p class="text-xs md:text-sm text-[#46483f] mt-2">Didesain untuk gamer antusias dan mitra bisnis yang menuntut keandalan dan transparansi penuh.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="flex flex-col gap-3 p-6 rounded-2xl bg-[#F4EFE6] border border-[#525A43]/15">
                    <div class="w-12 h-12 rounded-xl bg-[#525A43] text-white flex items-center justify-center font-bold shadow-xs">
                        <span class="material-symbols-outlined text-2xl">qr_code_2</span>
                    </div>
                    <h3 class="text-lg font-bold text-[#1F2419]">QRIS Dinamis 24 Jam</h3>
                    <p class="text-xs text-[#46483f] leading-relaxed">
                        Bayar instan dan praktis menggunakan QRIS Dinamis otomatis dari seluruh aplikasi Mobile Banking &amp; E-Wallet nasional tanpa biaya admin tambahan.
                    </p>
                </div>

                <div class="flex flex-col gap-3 p-6 rounded-2xl bg-[#F4EFE6] border border-[#525A43]/15">
                    <div class="w-12 h-12 rounded-xl bg-[#525A43] text-white flex items-center justify-center font-bold shadow-xs">
                        <span class="material-symbols-outlined text-2xl">bolt</span>
                    </div>
                    <h3 class="text-lg font-bold text-[#1F2419]">Transmisi Kilat 1–3 Detik</h3>
                    <p class="text-xs text-[#46483f] leading-relaxed">
                        Integrasi API langsung ke server resmi menjamin pesanan item game dan token PPOB langsung terkirim seketika setelah pembayaran terverifikasi.
                    </p>
                </div>

                <div class="flex flex-col gap-3 p-6 rounded-2xl bg-[#F4EFE6] border border-[#525A43]/15">
                    <div class="w-12 h-12 rounded-xl bg-[#525A43] text-white flex items-center justify-center font-bold shadow-xs">
                        <span class="material-symbols-outlined text-2xl">shield_check</span>
                    </div>
                    <h3 class="text-lg font-bold text-[#1F2419]">Snapshot Bukti Transparan</h3>
                    <p class="text-xs text-[#46483f] leading-relaxed">
                        Struk invoice resmi dengan nomor serial (SN) resmi yang dapat dicetak atau disimpan kapan saja secara real-time.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ SECTION -->
    <section class="max-w-content mx-auto w-full px-4 md:px-8 lg:px-12 py-10">
        <div class="max-w-3xl mx-auto flex flex-col gap-4">
            <div class="text-center mb-4">
                <span class="text-xs font-bold uppercase tracking-widest text-[#525A43]">Pertanyaan Umum</span>
                <h2 class="text-2xl font-extrabold text-[#1F2419] tracking-tight mt-1">Frequently Asked Questions</h2>
            </div>

            <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-2xl p-5 shadow-sm">
                <h4 class="text-sm font-bold text-[#1F2419] flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#525A43] text-base">help</span>
                    Berapa lama proses top up game di VAKSTORE?
                </h4>
                <p class="text-xs text-[#46483f] mt-2 leading-relaxed pl-6">
                    Seluruh pesanan diproses otomatis oleh sistem API Gateway dalam kurun waktu 1–3 detik setelah pembayaran QRIS terverifikasi.
                </p>
            </div>

            <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-2xl p-5 shadow-sm">
                <h4 class="text-sm font-bold text-[#1F2419] flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#525A43] text-base">help</span>
                    Metode pembayaran apa saja yang didukung?
                </h4>
                <p class="text-xs text-[#46483f] mt-2 leading-relaxed pl-6">
                    VAKSTORE mendukung pembayaran serba instan melalui QRIS Dinamis 24 Jam yang dapat di-scan dari seluruh aplikasi Mobile Banking (BCA, Mandiri, BRI, BNI, BSI, CIMB) serta E-Wallet (GoPay, OVO, DANA, ShopeePay, LinkAja).
                </p>
            </div>

            <div class="bg-[#FFFFFF] border border-[#525A43]/15 rounded-2xl p-5 shadow-sm">
                <h4 class="text-sm font-bold text-[#1F2419] flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#525A43] text-base">help</span>
                    Apakah saya bisa melacak status transaksi?
                </h4>
                <p class="text-xs text-[#46483f] mt-2 leading-relaxed pl-6">
                    Bisa! Anda cukup mengunjungi menu <a href="{{ route('tracking') }}" class="text-[#525A43] font-bold underline">Cek Transaksi</a> dan masukkan nomor invoice (contoh: TRX-...) atau nomor WhatsApp yang Anda daftarkan saat memesan.
                </p>
            </div>
        </div>
    </section>

</div>

@push('scripts')
<script>
    function handleHomeSearch(query) {
        const q = (query || '').toLowerCase().trim();
        const searchFeedback = document.getElementById('search-feedback');
        const clearBtn = document.getElementById('search-clear-btn');
        
        if (clearBtn) {
            clearBtn.classList.toggle('hidden', !q);
        }

        let foundCount = 0;
        
        // 1. Filter Game Cards
        const gameCards = document.querySelectorAll('.game-catalog-item');
        let gamesMatch = 0;
        gameCards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const publisher = (card.getAttribute('data-publisher') || '').toLowerCase();
            if (!q || name.includes(q) || publisher.includes(q)) {
                card.classList.remove('hidden');
                gamesMatch++;
                foundCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        // 2. Filter Pulsa Cards
        const pulsaCards = document.querySelectorAll('.pulsa-catalog-item');
        let pulsaMatch = 0;
        pulsaCards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            if (!q || name.includes(q)) {
                card.classList.remove('hidden');
                pulsaMatch++;
                foundCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        // 3. Filter Pascabayar Cards
        const pascaCards = document.querySelectorAll('.pasca-catalog-item');
        let pascaMatch = 0;
        pascaCards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            if (!q || name.includes(q)) {
                card.classList.remove('hidden');
                pascaMatch++;
                foundCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        if (searchFeedback) {
            if (q) {
                searchFeedback.classList.remove('hidden');
                searchFeedback.innerHTML = `🔍 Ditemukan <strong>${foundCount}</strong> produk/layanan cocok untuk pencarian "<strong>${query}</strong>"`;
            } else {
                searchFeedback.classList.add('hidden');
            }
        }
    }

    function submitHomeSearch(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('main-search-input');
        const query = (input?.value || '').trim();
        
        if (!query) {
            document.getElementById('game-section')?.scrollIntoView({ behavior: 'smooth' });
            return;
        }

        // If query looks like an invoice (starts with INV, TRX, or digits >= 6)
        const isInvoice = /^(inv|trx|\d{6,})/i.test(query);
        if (isInvoice) {
            window.location.href = "{{ route('tracking') }}?search=" + encodeURIComponent(query);
            return;
        }

        // Apply filter and scroll to game catalog
        handleHomeSearch(query);
        document.getElementById('game-section')?.scrollIntoView({ behavior: 'smooth' });
    }

    function searchByTag(tagName) {
        const input = document.getElementById('main-search-input');
        if (input) {
            input.value = tagName;
            handleHomeSearch(tagName);
            
            if (tagName.toLowerCase().includes('pln') || tagName.toLowerCase().includes('pdam')) {
                document.getElementById('pasca-section')?.scrollIntoView({ behavior: 'smooth' });
            } else if (tagName.toLowerCase().includes('pulsa')) {
                document.getElementById('pulsa-section')?.scrollIntoView({ behavior: 'smooth' });
            } else {
                document.getElementById('game-section')?.scrollIntoView({ behavior: 'smooth' });
            }
        }
    }

    function clearHomeSearch() {
        const input = document.getElementById('main-search-input');
        if (input) {
            input.value = '';
            handleHomeSearch('');
            input.focus();
        }
    }
</script>
@endpush
@endsection
