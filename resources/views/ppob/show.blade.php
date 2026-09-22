@extends('layouts.app')

@section('content')
<div class="flex flex-col w-full">

    <!-- Sub-Navigation / Breadcrumb & Status Ribbon -->
    <section class="w-full bg-[#f4ebe1]/80 border-b border-[#DCD1C2]">
        <div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-xs text-[#596152]">
                <a class="hover:text-[#1F2419] transition-colors" href="{{ route('home') }}">Beranda</a>
                <span class="material-symbols-outlined text-xs">chevron_right</span>
                <a class="hover:text-[#1F2419] transition-colors" href="{{ route('ppob.index') }}">Layanan PPOB</a>
                <span class="material-symbols-outlined text-xs">chevron_right</span>
                <span class="text-[#1F2419] font-bold">{{ $service->name }}</span>
            </div>
            <div class="flex items-center gap-2 bg-[#FFFFFF] border border-[#DCD1C2] px-3 py-1 rounded-full shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-xs text-[#2c3325] font-semibold">Biller PPOB Core: Online (SN &amp; Struk Realtime)</span>
            </div>
        </div>
    </section>

    @php
        $isPureBill = in_array($service->slug, ['pdam-nusantara', 'telkom-indihome']);
        $isPln = in_array($service->slug, ['pln', 'pln-pascabayar']);
        $isPlnPostpaid = ($service->slug === 'pln-pascabayar');
        $isPulsa = ($service->slug === 'pulsa-all-operator');

        $defaultProduct = $service->products->first();
        $plnPostpaidProduct = $isPlnPostpaid
            ? $service->products->firstWhere('provider_sku', 'plnpas1')
            : null;
        $plnPrepaidProducts = ($service->slug === 'pln')
            ? $service->products->where('sub_category', 'Prabayar (Token)')
            : collect();

        // Mapped PPOB Service Options from ALL active products.
        // Each option belongs to its own Product (e.g. PDAM Sumenep -> pd32 -> Product 356).
        $mainBillProduct = $isPureBill ? $service->products->first() : null;
        $productsById = $service->products->keyBy('id');

        $serviceOptions = $isPureBill
            ? $service->products
                ->flatMap(fn ($product) => $product->serviceOptions)
                ->filter(fn ($opt) => $opt->status === 'active')
                ->sortBy('name')
                ->values()
            : collect();

        $groupedOptions = $serviceOptions->groupBy(fn($opt) => $opt->region_name ?: 'Nasional');
    @endphp

    <!-- Main Split Architecture -->
    <div class="max-w-content mx-auto w-full px-4 md:px-8 lg:px-12 py-8">
        <form action="{{ route('ppob.checkout') }}" method="POST" id="ppob-form">
            @csrf
            <input type="hidden" name="product_id" id="selected-product-id" value="">
            <input type="hidden" name="service_option_id" id="selected-service-option-id" value="">
            <input type="hidden" name="bill_amount" id="selected-bill-amount" value="0">
            <input type="hidden" name="payment_method" id="selected-payment-method" value="qris">
            <input type="hidden" name="voucher_code" id="applied-voucher-code" value="">
            <input type="hidden" name="nickname" id="validated-nickname" value="">

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- LEFT FLOW COLUMN (7 Columns) -->
                <div class="lg:col-span-7 flex flex-col gap-6">
                    
                    <!-- Service Hero Banner Card -->
                    <div class="relative overflow-hidden rounded-3xl bg-[#FFFFFF] border border-[#DCD1C2] p-6 shadow-sm flex flex-col sm:flex-row items-center gap-6">
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl overflow-hidden shrink-0 shadow-sm bg-[#1E1915] border border-[#DCD1C2] relative flex items-center justify-center p-2">
                            <img class="w-full h-full object-contain" src="{{ asset($service->image) }}" alt="{{ $service->name }}" onerror="this.src='{{ asset('images/logo.png') }}'">
                            <div class="absolute bottom-0 inset-x-0 bg-[#2c3325]/90 backdrop-blur-xs py-0.5 text-center">
                                <span class="text-[9px] text-[#dee6c8] tracking-wider uppercase font-bold">{{ $service->publisher }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 text-center sm:text-left flex-1 min-w-0">
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                                <span class="bg-[#e2e7d7] text-[#333b28] font-bold px-2 py-0.5 rounded text-[10px] tracking-wider uppercase">PPOB RESMI</span>
                                <span class="bg-[#F4EFE6] border border-[#DCD1C2] text-[#596152] px-2 py-0.5 rounded text-[10px] flex items-center gap-1 font-semibold">
                                    <span class="material-symbols-outlined text-emerald-600 text-xs font-bold">bolt</span> SN &amp; Token Instan
                                </span>
                            </div>
                            <h1 class="text-2xl font-extrabold text-[#1F2419] tracking-tight">{{ $service->name }}</h1>
                            <p class="text-xs text-[#596152] leading-relaxed">
                                {{ $service->description }}
                            </p>
                        </div>
                    </div>

                    @if($isPln)
                        <!-- PLN DEDICATED STEP 1: PILIH TIPE LAYANAN (PRABAYAR / PASCABAYAR) -->
                        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4" id="pln-type-selection-box">
                            <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]/60">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">1</div>
                                    <div class="flex flex-col">
                                        <span class="text-base font-bold text-[#1F2419]">Pilih Jenis Layanan PLN</span>
                                        <span class="text-xs text-[#596152]">Pilih Token Prabayar (isi ulang) atau Bayar Tagihan Listrik Pascabayar (bulanan)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                                <!-- Option 1: Prabayar (Token) -->
                                <div 
                                    class="pln-type-card border-2 border-[#525A43] bg-[#F4EFE6]/50 rounded-2xl p-4 cursor-pointer hover:border-[#525A43] hover:shadow-md transition-all flex flex-col justify-between group"
                                    data-type="prabayar"
                                    onclick="selectPlnServiceType('prabayar', this)"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="w-10 h-10 rounded-xl bg-[#525A43] text-white flex items-center justify-center shadow-xs">
                                            <span class="material-symbols-outlined text-xl">bolt</span>
                                        </div>
                                        <span class="text-[10px] font-extrabold text-[#397341] bg-emerald-100 border border-emerald-200 px-2 py-0.5 rounded-full">
                                            Token Instan
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-[#1F2419] group-hover:text-[#525A43] transition-colors">Prabayar (Token Listrik)</h3>
                                        <p class="text-xs text-[#596152] mt-1 leading-relaxed">Beli 20 digit kode stroom token listrik nominal Rp 20.000 s.d. Rp 1.000.000.</p>
                                    </div>
                                    <div class="mt-4 pt-2.5 border-t border-[#DCD1C2]/60 flex items-center justify-between text-xs font-bold text-[#525A43]">
                                        <span>Pilih Nominal Token</span>
                                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                    </div>
                                </div>

                                <!-- Option 2: Pascabayar -->
                                <div 
                                    class="pln-type-card border-2 border-[#DCD1C2] bg-[#FFFFFF] rounded-2xl p-4 cursor-pointer hover:border-[#525A43] hover:shadow-md transition-all flex flex-col justify-between group"
                                    data-type="pascabayar"
                                    onclick="selectPlnServiceType('pascabayar', this)"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="w-10 h-10 rounded-xl bg-[#F4EFE6] border border-[#DCD1C2] text-[#525A43] flex items-center justify-center shadow-xs">
                                            <span class="material-symbols-outlined text-xl">receipt_long</span>
                                        </div>
                                        <span class="text-[10px] font-extrabold text-amber-800 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded-full">
                                            Tagihan Bulanan
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-extrabold text-[#1F2419] group-hover:text-[#525A43] transition-colors">Pascabayar (Tagihan Listrik)</h3>
                                        <p class="text-xs text-[#596152] mt-1 leading-relaxed">Cek &amp; bayar tagihan listrik bulanan resmi otomatis lunas tanpa perlu pilih nominal.</p>
                                    </div>
                                    <div class="mt-4 pt-2.5 border-t border-[#DCD1C2]/60 flex items-center justify-between text-xs font-bold text-[#525A43]">
                                        <span>Cek Rincian Tagihan</span>
                                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($isPureBill)
                        <!-- ================= PURE BILL FLOW (PDAM & INTERNET) ================= -->
                        
                        <!-- STEP 1: MASUKKAN ID PELANGGAN -->
                        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]/60">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                        1
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-base font-bold text-[#1F2419]">
                                            Masukkan ID Pelanggan
                                        </span>
                                        <span class="text-xs text-[#596152]">
                                            Masukkan {{ strtolower($service->target_field_name) }} dengan benar
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-1">
                                <label class="text-xs font-bold text-[#2c3325]" for="input-target">
                                    {{ $service->target_field_name }}
                                </label>
                                <div class="relative flex items-center">
                                    <input 
                                        name="target"
                                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-sm font-bold text-[#1F2419] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43] focus:bg-[#FFFFFF] focus:ring-2 focus:ring-[#525A43]/20 transition-all" 
                                        id="input-target" 
                                        placeholder="{{ $service->target_placeholder }}" 
                                        type="text" 
                                        value=""
                                        required
                                        oninput="onTargetInputChanged()"
                                    >
                                    <span class="material-symbols-outlined absolute right-3 text-[#878c7f] pointer-events-none text-base">pin</span>
                                </div>
                                <p class="text-[11px] text-[#596152] mt-0.5">
                                    * Masukkan nomor sambungan / ID pelanggan Anda tanpa spasi atau karakter khusus.
                                </p>
                            </div>
                        </div>

                        <!-- STEP 2: PILIH WILAYAH / PROVIDER -->
                        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4" id="step-biller-selection-box">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#DCD1C2]/60">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                        2
                                    </div>
                                    <div class="flex flex-col">
                                        <h2 class="text-base font-bold text-[#1F2419]">
                                            {{ $service->slug === 'pdam-nusantara' ? 'Pilih Wilayah PDAM' : 'Pilih Provider Internet' }}
                                        </h2>
                                        <p class="text-xs text-[#596152]">
                                            {{ $service->slug === 'pdam-nusantara' ? 'Cari dan pilih daerah PDAM tempat Anda berlangganan' : 'Ketik dan pilih provider internet yang Anda gunakan' }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2.5 py-0.5 rounded-full border border-emerald-200">
                                    {{ $serviceOptions->isNotEmpty() ? $serviceOptions->count() : $service->products->count() }} {{ $service->slug === 'pdam-nusantara' ? 'Pilihan Wilayah' : 'Pilihan Provider' }}
                                </span>
                            </div>

                            <!-- Single Searchable Dropdown Combobox -->
                            <div class="relative" id="custom-biller-combobox">
                                <div class="relative flex items-center">
                                    <span class="material-symbols-outlined absolute left-3.5 text-[#525A43] text-lg pointer-events-none">search</span>
                                    <input 
                                        type="text" 
                                        id="biller-combobox-input" 
                                        class="w-full h-11 bg-[#FFFFFF] border border-[#DCD1C2] pl-10 pr-10 rounded-xl text-xs font-bold text-[#1F2419] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43] focus:ring-2 focus:ring-[#525A43]/20 shadow-xs cursor-pointer"
                                        placeholder="{{ $service->slug === 'pdam-nusantara' ? '🔍 Ketik atau pilih nama wilayah PDAM (contoh: Surabaya, Bogor, Jakarta, Denpasar)...' : '🔍 Ketik atau pilih provider internet (IndiHome, Biznet, First Media, dll)...' }}"
                                        value=""
                                        readonly
                                        onclick="toggleBillerDropdown(event)"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="toggleBillerDropdown(event)" 
                                        class="absolute right-2.5 p-1 text-[#525A43] hover:text-[#1F2419] focus:outline-none cursor-pointer"
                                        id="biller-dropdown-toggle-btn"
                                    >
                                        <span class="material-symbols-outlined text-lg transition-transform duration-200" id="biller-dropdown-chevron">expand_more</span>
                                    </button>
                                </div>

                                <!-- Helper Text Underneath Input -->
                                <p class="text-[11px] text-[#596152] mt-1.5 flex items-center gap-1" id="biller-hint">
                                    <span class="material-symbols-outlined text-xs text-[#525A43]">info</span>
                                    <span>{{ $service->slug === 'pdam-nusantara' ? 'Klik kolom diatas untuk mencari wilayah' : 'Klik kolom diatas untuk mencari provider' }}</span>
                                </p>

                                <!-- Dropdown Menu List -->
                                <div 
                                    id="biller-dropdown-menu" 
                                    class="hidden absolute z-30 top-full left-0 right-0 mt-1.5 bg-[#FFFFFF] border border-[#525A43]/25 rounded-2xl shadow-2xl max-h-72 overflow-y-auto p-2"
                                >
                                    <!-- Live Filter Search Box Inside Dropdown -->
                                    <div class="p-1.5 sticky top-0 bg-[#FFFFFF] z-10 pb-2 border-b border-[#DCD1C2]/60">
                                        <div class="relative flex items-center">
                                            <span class="material-symbols-outlined absolute left-2.5 text-[#76786f] text-sm pointer-events-none">search</span>
                                            <input 
                                                type="text" 
                                                id="biller-search-filter" 
                                                placeholder="{{ $service->slug === 'pdam-nusantara' ? 'Ketik untuk mencari nama daerah...' : 'Ketik untuk mencari provider internet...' }}" 
                                                class="w-full h-9 bg-[#F4EFE6] border border-[#DCD1C2] pl-8 pr-3 rounded-lg text-xs font-semibold text-[#1F2419] placeholder:text-[#76786f] focus:outline-none focus:border-[#525A43]"
                                                oninput="filterCustomBillerDropdown(this.value)"
                                            >
                                        </div>
                                    </div>

                                    <div class="pt-1 flex flex-col gap-1" id="biller-items-container">
                                        @if($serviceOptions->isNotEmpty())
                                            @foreach($groupedOptions as $regionName => $options)
                                                <div class="biller-group-section mb-2" data-group-name="{{ strtolower($regionName) }}">
                                                    <div class="text-[10px] font-extrabold uppercase text-[#525A43] bg-[#F4EFE6] px-2.5 py-1 rounded-md mb-1 tracking-wider sticky top-11">
                                                        📍 {{ $regionName }}
                                                    </div>
                                                    <div class="flex flex-col gap-0.5 pl-1">
                                                        @foreach($options as $opt)
                                                            <div 
                                                                class="biller-option-item px-3 py-2 rounded-xl text-xs font-semibold text-[#1F2419] hover:bg-[#525A43] hover:text-white cursor-pointer transition-colors flex items-center justify-between group/item"
                                                                data-id="{{ $opt->id }}"
                                                                data-product-id="{{ $opt->product_id }}"
                                                                data-name="{{ $opt->name }}"
                                                                data-admin="{{ $productsById->get($opt->product_id)?->selling_price ?? 2500 }}"
                                                                data-sku="{{ $opt->buyer_sku_code }}"
                                                                onclick="selectCustomBillerOption({{ $opt->id }}, {{ $opt->product_id }}, '{{ addslashes($opt->name) }}', {{ $productsById->get($opt->product_id)?->selling_price ?? 2500 }}, '{{ $opt->buyer_sku_code }}', event)"
                                                            >
                                                                <span class="truncate pr-2">{{ $opt->name }}</span>
                                                                <span class="material-symbols-outlined text-sm opacity-0 group-hover/item:opacity-100 transition-opacity">check</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            @foreach($groupedProducts as $subCatName => $prods)
                                                <div class="biller-group-section mb-2" data-group-name="{{ strtolower($subCatName) }}">
                                                    <div class="text-[10px] font-extrabold uppercase text-[#525A43] bg-[#F4EFE6] px-2.5 py-1 rounded-md mb-1 tracking-wider sticky top-11">
                                                        📍 {{ $subCatName }}
                                                    </div>
                                                    <div class="flex flex-col gap-0.5 pl-1">
                                                        @foreach($prods as $prod)
                                                            <div 
                                                                class="biller-option-item px-3 py-2 rounded-xl text-xs font-semibold text-[#1F2419] hover:bg-[#525A43] hover:text-white cursor-pointer transition-colors flex items-center justify-between group/item"
                                                                data-id="{{ $prod->id }}"
                                                                data-product-id="{{ $prod->id }}"
                                                                data-name="{{ $prod->name }}"
                                                                data-admin="{{ $prod->selling_price }}"
                                                                data-sku="{{ $prod->provider_sku }}"
                                                                onclick="selectCustomBillerOption(null, {{ $prod->id }}, '{{ addslashes($prod->name) }}', {{ $prod->selling_price }}, '{{ $prod->provider_sku }}', event)"
                                                            >
                                                                <span class="truncate pr-2">{{ $prod->name }}</span>
                                                                <span class="material-symbols-outlined text-sm opacity-0 group-hover/item:opacity-100 transition-opacity">check</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>

                                <!-- Hidden select for form consistency -->
                                <select id="select-biller-product" class="hidden" onchange="onSelectBillerProduct(this)">
                                    <option value="" disabled selected>Pilih Wilayah / Provider</option>
                                    @if($serviceOptions->isNotEmpty())
                                        @foreach($groupedOptions as $regionName => $options)
                                            <optgroup label="📍 {{ $regionName }}" class="biller-group">
                                                @foreach($options as $opt)
                                                    <option value="{{ $opt->id }}" data-product-id="{{ $opt->product_id }}" data-sku="{{ $opt->buyer_sku_code }}" data-name="{{ $opt->name }}" data-admin="{{ $productsById->get($opt->product_id)?->selling_price ?? 2500 }}" class="biller-option">
                                                        {{ $opt->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @else
                                        @foreach($groupedProducts as $subCatName => $prods)
                                            <optgroup label="📍 {{ $subCatName }}" class="biller-group">
                                                @foreach($prods as $prod)
                                                    <option value="{{ $prod->id }}" data-product-id="{{ $prod->id }}" data-sku="{{ $prod->provider_sku }}" data-name="{{ $prod->name }}" data-admin="{{ $prod->selling_price }}" class="biller-option">
                                                        {{ $prod->name }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <!-- STEP 3: CEK RINCIAN TAGIHAN -->
                        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4" id="step-inquiry-box">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#DCD1C2]/60">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                        3
                                    </div>
                                    <div class="flex flex-col">
                                        <h2 class="text-base font-bold text-[#1F2419]">
                                            Cek Rincian Tagihan
                                        </h2>
                                        <p class="text-xs text-[#596152]">
                                            Periksa keabsahan pelanggan dan jumlah tagihan resmi sebelum pembayaran
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Check Button -->
                            <div>
                                <button type="button" class="w-full h-12 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-2xl text-sm font-bold flex items-center justify-center gap-2 transition-all shadow-xs cursor-pointer" id="btn-validate" onclick="handleCheckBillClick()">
                                    <span class="material-symbols-outlined text-lg" id="btn-validate-icon">receipt_long</span>
                                    <span id="btn-validate-text">Cek Tagihan Sekarang</span>
                                </button>
                            </div>

                            <!-- Provider Error Alert Box (Hidden by default, shown when inquiry fails) -->
                            <div id="bill-error-box" class="hidden bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 flex items-start gap-3 transition-all">
                                <span class="material-symbols-outlined text-rose-600 text-xl shrink-0 mt-0.5">error</span>
                                <div class="flex flex-col">
                                    <span class="text-xs font-extrabold text-rose-900" id="bill-error-title">Gagal Memeriksa Tagihan</span>
                                    <span class="text-xs text-rose-700 mt-0.5 leading-relaxed" id="bill-error-message"></span>
                                </div>
                            </div>

                            <!-- Verified Result & Detailed Breakdown (Hidden until inquiry succeeds) -->
                            <div id="bill-details-container" class="hidden flex flex-col gap-4 transition-all">
                                <!-- Verified Badge -->
                                <div class="bg-[#F4EFE6] border border-[#DCD1C2] rounded-2xl p-3.5 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-[#397341]/15 text-[#397341] flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-lg">verified</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] text-[#596152] uppercase font-bold">Pelanggan Terverifikasi Server</span>
                                            <span class="text-xs font-extrabold text-[#1F2419] tracking-wide" id="bill-customer-name-verified">
                                                -
                                            </span>
                                        </div>
                                    </div>
                                    <span class="bg-[#397341]/15 text-[#397341] text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-[#397341]/25">Tagihan Valid</span>
                                </div>

                                <!-- Detailed Bill Breakdown Display -->
                                <div class="bg-[#F6F0E8] border border-[#DCD1C2] rounded-2xl p-4.5 flex flex-col gap-3">
                                    <div class="flex items-center justify-between pb-2 border-b border-[#DCD1C2]/60">
                                        <div class="flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[#525A43] text-lg">{{ $service->slug === 'pdam-nusantara' ? 'water_drop' : 'router' }}</span>
                                            <span class="text-xs font-extrabold text-[#1F2419]" id="selected-biller-header">Tagihan Resmi Biller</span>
                                        </div>
                                        <span class="text-[10px] font-bold bg-[#525A43] text-white px-2 py-0.5 rounded-md">Tagihan Bulanan</span>
                                    </div>

                                    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-xl p-3.5 divide-y divide-[#DCD1C2]/60 text-xs">
                                        <div class="flex justify-between py-2">
                                            <span class="text-[#596152]">ID Pelanggan:</span>
                                            <span class="font-bold text-[#1F2419]" id="bill-target-display">-</span>
                                        </div>
                                        <div class="flex justify-between py-2">
                                            <span class="text-[#596152]">{{ $service->slug === 'pdam-nusantara' ? 'Wilayah PDAM:' : 'Provider Internet:' }}</span>
                                            <span class="font-bold text-[#1F2419]" id="bill-region-display">-</span>
                                        </div>
                                        <div class="flex justify-between py-2">
                                            <span class="text-[#596152]">Nama Pelanggan:</span>
                                            <span class="font-bold text-emerald-800" id="bill-customer-name">-</span>
                                        </div>
                                        <div class="flex justify-between py-2">
                                            <span class="text-[#596152]">Periode Tagihan:</span>
                                            <span class="font-bold text-[#1F2419]" id="bill-period-display">-</span>
                                        </div>
                                        <div class="flex justify-between py-2">
                                            <span class="text-[#596152]">Tagihan Pokok:</span>
                                            <span class="font-bold text-[#1F2419]" id="bill-base-display">Rp 0</span>
                                        </div>
                                        <div class="flex justify-between py-2">
                                            <span class="text-[#596152]">Biaya Layanan:</span>
                                            <span class="font-bold text-[#525A43]" id="bill-fee-display">Rp 0</span>
                                        </div>
                                        <div class="flex justify-between pt-2.5 font-bold text-sm">
                                            <span class="text-[#1F2419]">Total Pembayaran:</span>
                                            <span class="text-[#525A43]" id="bill-total-display">Rp 0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @else
                        <!-- ================= PLN & PULSA FLOW ================= -->
                        
                        <!-- STEP INPUT TARGET / ID PELANGGAN -->
                        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4">
                            <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]/60">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs" id="step-target-badge">
                                        {{ $isPln ? '2' : '1' }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-base font-bold text-[#1F2419]" id="step-target-title">
                                            {{ $isPln ? 'Masukkan No. Meter / ID Pelanggan PLN' : 'Nomor Tujuan / ID Pelanggan' }}
                                        </span>
                                        <span class="text-xs text-[#596152]" id="step-target-subtitle">
                                            Masukkan {{ strtolower($service->target_field_name) }} dengan benar
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
                                <div class="{{ $isPln ? 'sm:col-span-8' : 'sm:col-span-12' }} flex flex-col gap-1">
                                    <label class="text-xs font-bold text-[#2c3325]" for="input-target" id="input-target-label">
                                        {{ $service->target_field_name }}
                                    </label>
                                    <div class="relative flex items-center">
                                        <input 
                                            name="target"
                                            class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-sm font-bold text-[#1F2419] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43] focus:bg-[#FFFFFF] focus:ring-2 focus:ring-[#525A43]/20 transition-all" 
                                            id="input-target" 
                                            placeholder="{{ $service->target_placeholder }}" 
                                            type="text" 
                                            value=""
                                            required
                                            oninput="onTargetInputChanged()"
                                        >
                                        <span class="material-symbols-outlined absolute right-3 text-[#878c7f] pointer-events-none text-base">pin</span>
                                    </div>
                                </div>

                                @if($isPln)
                                    <div class="sm:col-span-4">
                                        <button type="button" class="w-full h-11 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-colors shadow-xs cursor-pointer" id="btn-validate" onclick="handlePlnCheckClick()">
                                            <span class="material-symbols-outlined text-base">search</span>
                                            <span id="btn-validate-text">Cek Tagihan / ID</span>
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @if($isPln)
                                <!-- Verified Pelanggan Badge for PLN -->
                                <div class="bg-[#F4EFE6] border border-[#DCD1C2] rounded-2xl p-3 flex items-center justify-between transition-all" id="verified-box">
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-full bg-[#397341]/15 text-[#397341] flex items-center justify-center shrink-0">
                                            <span class="material-symbols-outlined text-base">verified</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] text-[#596152] uppercase font-bold">Pelanggan Terverifikasi Server</span>
                                            <span class="text-xs font-extrabold text-[#1F2419] tracking-wide" id="customer-nickname">
                                                -
                                            </span>
                                        </div>
                                    </div>
                                    <span class="bg-[#397341]/15 text-[#397341] text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-[#397341]/25">Online</span>
                                </div>
                            @endif
                        </div>

                        <!-- STEP: PRODUCT SELECTION / RINCIAN TAGIHAN -->
                        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4" id="step-product-box">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#DCD1C2]/60">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs" id="step-product-badge">
                                        {{ $isPln ? '3' : '2' }}
                                    </div>
                                    <div class="flex flex-col">
                                        <h2 class="text-base font-bold text-[#1F2419]" id="step-product-title">
                                            {{ $isPln ? 'Pilih Nominal Token Listrik' : 'Pilih Kategori & Nominal Produk' }}
                                        </h2>
                                        <p class="text-xs text-[#596152]" id="step-product-subtitle">
                                            {{ $isPln ? 'Pilih nominal token yang ingin diisi ke nomor meter Anda' : 'Pilih kuota paket data atau pulsa reguler instan' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 1. PLN FLOW SECTIONS -->
                            @if($isPln)
                                <!-- A. PLN Prabayar (Token Grid) -->
                                <div id="pln-prabayar-section" class="space-y-3">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                        @foreach($plnPrepaidProducts as $index => $prod)
                                            <div 
                                                class="product-item group relative bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-4 cursor-pointer transition-all duration-200 hover:border-[#525A43] hover:shadow-md flex flex-col justify-between"
                                                data-id="{{ $prod->id }}"
                                                data-name="{{ $prod->name }}"
                                                data-price="{{ $prod->selling_price }}"
                                                data-is-bill="false"
                                                onclick="selectProduct({{ $prod->id }}, '{{ addslashes($prod->name) }}', {{ $prod->selling_price }}, false, 0, this)"
                                            >
                                                <div class="flex items-start justify-between mb-2">
                                                    <div class="w-8 h-8 rounded-xl bg-[#F4EFE6] border border-[#DCD1C2] flex items-center justify-center text-[#525A43]">
                                                        <span class="material-symbols-outlined text-base">bolt</span>
                                                    </div>
                                                    @if($prod->badge)
                                                        <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full {{ $prod->badge === 'POPULER' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                                            {{ $prod->badge }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex flex-col">
                                                    <span class="text-[10px] text-[#596152] font-semibold">{{ $prod->sub_category }}</span>
                                                    <h3 class="text-xs font-bold text-[#1F2419] group-hover:text-[#525A43] transition-colors line-clamp-2 leading-snug">{{ $prod->name }}</h3>
                                                </div>
                                                <div class="mt-3 pt-2 border-t border-[#DCD1C2]/60 flex items-center justify-between">
                                                    <span class="text-[10px] text-[#596152]">Harga</span>
                                                    <span class="text-sm font-extrabold text-[#525A43]">Rp {{ number_format($prod->selling_price, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- B. PLN Pascabayar (Rincian Tagihan) -->
                                <div id="pln-pascabayar-section" class="hidden space-y-4">
                                    <div class="bg-[#F6F0E8] border border-[#DCD1C2] rounded-2xl p-5 flex flex-col gap-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-[#525A43] text-white flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-xl">receipt_long</span>
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-extrabold text-[#1F2419]">Rincian Tagihan Listrik PLN Pascabayar</h4>
                                                    <p class="text-xs text-[#596152]">Tagihan rekening listrik pascabayar sesuai pemakaian kWh pelanggan.</p>
                                                </div>
                                            </div>
                                            <span class="text-[10px] font-bold bg-[#525A43] text-white px-2.5 py-1 rounded-full">Tagihan Bulanan</span>
                                        </div>

                                        <!-- Dynamic Bill Breakdown Display -->
                                        <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-xl p-4 divide-y divide-[#DCD1C2]/60 text-xs">
                                            <div class="flex justify-between py-2">
                                                <span class="text-[#596152]">ID Pelanggan PLN:</span>
                                                <span class="font-bold text-[#1F2419]" id="pln-postpaid-target">-</span>
                                            </div>
                                            <div class="flex justify-between py-2">
                                                <span class="text-[#596152]">Nama Pelanggan:</span>
                                                <span class="font-bold text-emerald-800" id="pln-postpaid-name">-</span>
                                            </div>
                                            <div class="flex justify-between py-2">
                                                <span class="text-[#596152]">Periode Tagihan:</span>
                                                <span class="font-bold text-[#1F2419]" id="pln-postpaid-period">-</span>
                                            </div>
                                            <div class="flex justify-between py-2">
                                                <span class="text-[#596152]">Tagihan Pokok PLN (Biller):</span>
                                                <span class="font-bold text-[#1F2419]" id="pln-postpaid-base">Rp 0</span>
                                            </div>
                                            <div class="flex justify-between py-2">
                                                <span class="text-[#596152]">Biaya Layanan:</span>
                                                <span class="font-bold text-[#525A43]" id="pln-postpaid-fee">Rp {{ number_format($plnPostpaidProduct->selling_price ?? 2500, 0, ',', '.') }}</span>
                                            </div>
                                            <div class="flex justify-between pt-2.5 font-bold text-sm">
                                                <span class="text-[#1F2419]">Total Tagihan Listrik:</span>
                                                <span class="text-[#525A43]" id="pln-postpaid-total">Rp 0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- 2. PULSA & PAKET DATA FLOW -->
                            @if($isPulsa)
                                <div class="flex items-center gap-2 p-1.5 bg-[#F6F0E8] rounded-2xl border border-[#DCD1C2] overflow-x-auto">
                                    @foreach($groupedProducts as $subCatName => $items)
                                        <button 
                                            type="button" 
                                            class="subcat-tab-btn flex-1 py-2 px-4 rounded-xl text-xs font-extrabold transition-all text-center whitespace-nowrap {{ $loop->first ? 'bg-[#525A43] text-white shadow-xs' : 'text-[#1F2419] hover:bg-[#EAE1D4]' }}"
                                            data-target-tab="{{ Str::slug($subCatName) }}"
                                            onclick="switchPulsaSubCategoryTab('{{ Str::slug($subCatName) }}', this)"
                                        >
                                            @if(str_contains(strtolower($subCatName), 'paket data'))
                                                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-sm">wifi</span> Paket Data Internet</span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-sm">phone_iphone</span> Pulsa Reguler</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>

                                @foreach($groupedProducts as $subCatName => $items)
                                    @php $subSlug = Str::slug($subCatName); @endphp
                                    <div class="pulsa-panel {{ $loop->first ? '' : 'hidden' }}" id="panel-{{ $subSlug }}">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                            @foreach($items as $index => $prod)
                                                <div 
                                                    class="product-item group relative bg-[#FFFFFF] border border-[#DCD1C2] rounded-2xl p-4 cursor-pointer transition-all duration-200 hover:border-[#525A43] hover:shadow-md flex flex-col justify-between"
                                                    data-id="{{ $prod->id }}"
                                                    data-name="{{ $prod->name }}"
                                                    data-price="{{ $prod->selling_price }}"
                                                    data-is-bill="false"
                                                    onclick="selectProduct({{ $prod->id }}, '{{ addslashes($prod->name) }}', {{ $prod->selling_price }}, false, 0, this)"
                                                >
                                                    <div class="flex items-start justify-between mb-2">
                                                        <div class="w-8 h-8 rounded-xl bg-[#F4EFE6] border border-[#DCD1C2] flex items-center justify-center text-[#525A43]">
                                                            <span class="material-symbols-outlined text-base">
                                                                {{ str_contains(strtolower($prod->name), 'data') ? 'wifi' : 'phone_iphone' }}
                                                            </span>
                                                        </div>
                                                        @if($prod->badge)
                                                            <span class="text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-full {{ $prod->badge === 'POPULER' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                                                {{ $prod->badge }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <span class="text-[10px] text-[#596152] font-semibold">{{ $prod->sub_category }}</span>
                                                        <h3 class="text-xs font-bold text-[#1F2419] group-hover:text-[#525A43] transition-colors line-clamp-2 leading-snug">{{ $prod->name }}</h3>
                                                    </div>
                                                    <div class="mt-3 pt-2 border-t border-[#DCD1C2]/60 flex items-center justify-between">
                                                        <span class="text-[10px] text-[#596152]">Harga</span>
                                                        <span class="text-sm font-extrabold text-[#525A43]">Rp {{ number_format($prod->selling_price, 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endif

                    <!-- STEP: PAYMENT METHOD -->
                    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-5">
                        <div class="flex items-center gap-3 pb-3 border-b border-[#DCD1C2]/60">
                            <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                {{ ($isPln || $isPureBill) ? '4' : '3' }}
                            </div>
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
                                <span class="text-base font-extrabold text-[#525A43]" id="price-qris">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- STEP: PROMO VOUCHER & CONTACT -->
                    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 shadow-sm flex flex-col gap-4">
                        <div class="flex items-center gap-3 pb-3 border-b border-[#DCD1C2]/60">
                            <div class="w-8 h-8 rounded-xl bg-[#525A43] text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                {{ ($isPln || $isPureBill) ? '5' : '4' }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-base font-bold text-[#1F2419]">Kode Voucher &amp; Kontak Notifikasi</span>
                                <span class="text-xs text-[#596152]">Klaim potongan diskon dan masukkan kontak pengiriman invoice</span>
                            </div>
                        </div>

                        <!-- Voucher Input -->
                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-bold text-[#2c3325]">Kode Promo / Voucher Diskon</label>
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input 
                                        type="text" 
                                        id="voucher-input" 
                                        placeholder="Contoh: TOPUPHEMAT / RAMADANKAREEM" 
                                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 uppercase font-bold text-sm text-[#1F2419] rounded-xl focus:outline-none focus:border-[#525A43]"
                                    >
                                </div>
                                <button 
                                    type="button" 
                                    id="btn-apply-voucher" 
                                    class="h-11 px-5 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-bold transition-colors shadow-xs cursor-pointer"
                                >
                                    Terapkan
                                </button>
                            </div>
                            <span id="voucher-status-text" class="text-xs font-bold hidden"></span>
                        </div>

                        <!-- Customer Contact -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-[#2c3325]">Nomor WhatsApp</label>
                                    <span class="text-[10px] text-[#596152]">(Salah satu wajib diisi)</span>
                                </div>
                                <input 
                                    type="text" 
                                    name="customer_phone" 
                                    id="customer-phone"
                                    value="" 
                                    oninput="checkPpobFormValidity()"
                                    class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 text-xs font-bold text-[#1F2419] rounded-xl focus:outline-none focus:border-[#525A43]" 
                                    placeholder="Contoh: 081234567890" 
                                >
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-[#2c3325]">Email Notifikasi</label>
                                    <span class="text-[10px] text-[#596152]">(Bantuan &amp; Invoice)</span>
                                </div>
                                <input 
                                    type="email" 
                                    name="customer_email" 
                                    id="customer-email"
                                    value="" 
                                    oninput="checkPpobFormValidity()"
                                    class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 text-xs font-bold text-[#1F2419] rounded-xl focus:outline-none focus:border-[#525A43]" 
                                    placeholder="contoh: user@gmail.com" 
                                >
                            </div>
                        </div>
                        <p class="text-[11px] text-[#596152] mt-1">
                            * Isi nomor WhatsApp atau alamat Email untuk menerima notifikasi status pengisian &amp; bantuan jika ada kendala.
                        </p>
                    </div>

                </div>

                <!-- RIGHT FLOATING ORDER SUMMARY COLUMN (5 Columns) -->
                <div class="lg:col-span-5 sticky top-28">
                    <div class="bg-[#FFFFFF] border border-[#525A43]/20 rounded-3xl p-6 shadow-md flex flex-col gap-5">
                        <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]">
                            <h3 class="text-base font-extrabold text-[#1F2419]">Ringkasan Pesanan PPOB</h3>
                            <span class="text-[10px] font-bold bg-[#F4EFE6] text-[#525A43] px-2.5 py-1 rounded-full">Resmi Biller Core</span>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div class="flex justify-between text-[#596152]">
                                <span>Layanan:</span>
                                <span class="font-bold text-[#1F2419] text-right">{{ $service->name }}</span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Produk / Kategori:</span>
                                <span class="font-bold text-[#1F2419] text-right" id="summary-product-name">-</span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Nomor Tujuan / ID:</span>
                                <span class="font-bold text-[#1F2419]" id="summary-target">-</span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Nama / Biller Pelanggan:</span>
                                <span class="font-bold text-emerald-800 text-right" id="summary-nickname">
                                    -
                                </span>
                            </div>
                            <div class="flex justify-between text-[#596152]">
                                <span>Metode Pembayaran:</span>
                                <span class="font-bold text-[#1F2419] uppercase" id="summary-payment">QRIS DINAMIS</span>
                            </div>

                            <div class="pt-3 border-t border-[#DCD1C2]/60 space-y-2">
                                <div class="flex justify-between text-[#596152]" id="summary-base-bill-row">
                                    <span id="summary-price-label">Harga Produk:</span>
                                    <span class="font-bold text-[#1F2419]" id="summary-product-price">Rp 0</span>
                                </div>
                                <div class="flex justify-between text-[#596152]">
                                    <span>Biaya Layanan:</span>
                                    <span class="font-bold text-[#1F2419]" id="summary-admin-fee">Rp 0</span>
                                </div>
                                <div class="flex justify-between text-emerald-700 hidden" id="summary-discount-row">
                                    <span>Potongan Voucher:</span>
                                    <span class="font-bold" id="summary-discount">- Rp 0</span>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-[#525A43]/20 flex items-center justify-between">
                                <div class="flex flex-col">
                                    <span class="text-[11px] text-[#596152] font-semibold">Total Pembayaran</span>
                                    <span class="text-2xl font-black text-[#525A43]" id="summary-grand-total">Rp 0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Checkout Button with Status Feedback -->
                        <div class="flex flex-col gap-2">
                            <button 
                                type="submit" 
                                id="btn-submit-order"
                                class="w-full h-12 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-sm font-extrabold transition-all duration-200 shadow-md flex items-center justify-center gap-2 cursor-pointer mt-1 opacity-50 cursor-not-allowed pointer-events-none"
                                disabled
                            >
                                <span class="material-symbols-outlined text-lg">shopping_cart_checkout</span>
                                <span id="btn-submit-text">Bayar Sekarang &amp; Proses Otomatis</span>
                            </button>

                            <p id="form-validation-warning" class="text-[11px] text-amber-800 bg-amber-50 border border-amber-200/80 rounded-xl p-2.5 text-center font-medium">
                                ⚠️ Lengkapi nomor tujuan, pilih produk/wilayah, dan isi WhatsApp atau Email untuk melanjutkan pembayaran.
                            </p>
                        </div>

                        <div class="flex items-center justify-center gap-2 text-[11px] text-[#596152]">
                            <span class="material-symbols-outlined text-sm text-emerald-700">lock</span>
                            <span>Enkripsi 256-Bit SSL • Transaksi Terlindungi Garansi 100%</span>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

</div>

@push('scripts')
<script>
    let isBillMode = {{ ($isPureBill || $isPlnPostpaid) ? 'true' : 'false' }};
    let currentBillAmount = 0;
    let currentPrice = 0;
    let currentFee = 0;
    let currentDiscount = 0;
    let selectedMethod = 'qris';
    let plnSelectedType = 'prabayar';

    function recalculateSummary() {
        const grandTotal = Math.max(0, currentPrice + currentFee - currentDiscount);
        document.getElementById('summary-product-price').innerText = 'Rp ' + currentPrice.toLocaleString('id-ID');
        document.getElementById('summary-admin-fee').innerText = 'Rp ' + currentFee.toLocaleString('id-ID');
        document.getElementById('summary-grand-total').innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
        
        if (currentDiscount > 0) {
            document.getElementById('summary-discount-row').classList.remove('hidden');
            document.getElementById('summary-discount').innerText = '- Rp ' + currentDiscount.toLocaleString('id-ID');
        } else {
            document.getElementById('summary-discount-row').classList.add('hidden');
        }

        const priceEl = document.getElementById('price-qris');
        if (priceEl) {
            priceEl.innerText = 'Rp ' + grandTotal.toLocaleString('id-ID');
        }
    }

    function selectProduct(id, name, price, isBill, billAmt, element) {
        document.getElementById('selected-product-id').value = id;
        document.getElementById('summary-product-name').innerText = name;
        
        isBillMode = isBill;
        if (isBill) {
            currentBillAmount = billAmt;
            currentPrice = billAmt + price;
            document.getElementById('selected-bill-amount').value = billAmt;
            document.getElementById('summary-price-label').innerText = 'Tagihan Pokok + Admin:';
        } else {
            currentBillAmount = 0;
            currentPrice = price;
            document.getElementById('selected-bill-amount').value = 0;
            document.getElementById('summary-price-label').innerText = 'Harga Produk:';
        }

        if (element) {
            document.querySelectorAll('.product-item').forEach(el => {
                el.classList.remove('border-[#525A43]', 'ring-2', 'ring-[#525A43]/20', 'bg-[#F4EFE6]/40');
                el.classList.add('border-[#DCD1C2]');
            });

            element.classList.remove('border-[#DCD1C2]');
            element.classList.add('border-[#525A43]', 'ring-2', 'ring-[#525A43]/20', 'bg-[#F4EFE6]/40');
        }

        recalculateSummary();
        checkPpobFormValidity();
    }

    // PLN TYPE SELECTION (Prabayar vs Pascabayar)
    function selectPlnServiceType(type, element) {
        plnSelectedType = type;

        // Toggle card style
        document.querySelectorAll('.pln-type-card').forEach(card => {
            card.classList.remove('border-[#525A43]', 'bg-[#F4EFE6]/50');
            card.classList.add('border-[#DCD1C2]', 'bg-[#FFFFFF]');
        });
        element.classList.add('border-[#525A43]', 'bg-[#F4EFE6]/50');
        element.classList.remove('border-[#DCD1C2]', 'bg-[#FFFFFF]');

        const prabayarSec = document.getElementById('pln-prabayar-section');
        const pascabayarSec = document.getElementById('pln-pascabayar-section');
        const titleEl = document.getElementById('step-product-title');
        const subtitleEl = document.getElementById('step-product-subtitle');
        const targetLabel = document.getElementById('input-target-label');

        if (type === 'pascabayar') {
            // Mode Pascabayar
            prabayarSec.classList.add('hidden');
            pascabayarSec.classList.remove('hidden');

            titleEl.innerText = 'Rincian Tagihan Listrik PLN Pascabayar';
            subtitleEl.innerText = 'Rincian tagihan rekening listrik pelanggan langsung terhitung otomatis';
            if (targetLabel) targetLabel.innerText = 'ID Pelanggan PLN Pascabayar';

            // Auto-select PLN-POSTPAID product
            const postpaidId = '{{ $plnPostpaidProduct->id ?? '' }}';
            const postpaidName = 'Tagihan Listrik PLN Pascabayar';
            const adminFee = {{ $plnPostpaidProduct->selling_price ?? 2500 }};
            const baseBill = 0;

            selectProduct(postpaidId, postpaidName, adminFee, true, baseBill, null);
            
            // Trigger inquiry immediately if target is present
            triggerInquiry('pln-pascabayar');
        } else {
            // Mode Prabayar
            prabayarSec.classList.remove('hidden');
            pascabayarSec.classList.add('hidden');

            titleEl.innerText = 'Pilih Nominal Token Listrik';
            subtitleEl.innerText = 'Pilih nominal token yang ingin diisi ke nomor meter Anda';
            if (targetLabel) targetLabel.innerText = 'No. Meter / ID Pelanggan PLN';

            // Reset selection until user clicks
            document.getElementById('selected-product-id').value = '';
            document.getElementById('summary-product-name').innerText = '-';
            currentPrice = 0;
            currentBillAmount = 0;
            recalculateSummary();
            checkPpobFormValidity();
        }
    }

    // PULSA SUB-CATEGORY TABS
    function switchPulsaSubCategoryTab(tabSlug, element) {
        document.querySelectorAll('.subcat-tab-btn').forEach(btn => {
            btn.classList.remove('bg-[#525A43]', 'text-white', 'shadow-xs');
            btn.classList.add('text-[#1F2419]');
        });
        element.classList.add('bg-[#525A43]', 'text-white', 'shadow-xs');
        element.classList.remove('text-[#1F2419]');

        document.querySelectorAll('.pulsa-panel').forEach(panel => {
            panel.classList.add('hidden');
        });

        const activePanel = document.getElementById('panel-' + tabSlug);
        if (activePanel) {
            activePanel.classList.remove('hidden');
        }
    }

    let selectedProviderSku = '';

    // Target input sync handler
    function onTargetInputChanged() {
        const targetInput = document.getElementById('input-target');
        const val = targetInput ? targetInput.value.trim() : '';
        
        const summaryTarget = document.getElementById('summary-target');
        if (summaryTarget) summaryTarget.innerText = val || '-';
        
        const plnTargetEl = document.getElementById('pln-postpaid-target');
        if (plnTargetEl) plnTargetEl.innerText = val || '-';

        const billTargetEl = document.getElementById('bill-target-display');
        if (billTargetEl) billTargetEl.innerText = val || '-';

        if (isBillMode) {
            // When ID changes, reset verified state so user must re-check bill
            currentBillAmount = 0;
            document.getElementById('selected-bill-amount').value = 0;
            currentPrice = currentFee;
            
            const detailsContainer = document.getElementById('bill-details-container');
            if (detailsContainer) detailsContainer.classList.add('hidden');
            
            const errorBox = document.getElementById('bill-error-box');
            if (errorBox) errorBox.classList.add('hidden');

            const btnText = document.getElementById('btn-validate-text');
            if (btnText) btnText.innerText = 'Cek Tagihan Sekarang';

            const btnIcon = document.getElementById('btn-validate-icon');
            if (btnIcon) {
                btnIcon.innerText = 'receipt_long';
                btnIcon.classList.remove('animate-spin');
            }
            
            const summaryNick = document.getElementById('summary-nickname');
            if (summaryNick) summaryNick.innerText = '-';
            
            document.getElementById('validated-nickname').value = '';
            recalculateSummary();
        }

        checkPpobFormValidity();
    }

    document.getElementById('input-target').addEventListener('input', onTargetInputChanged);

    // Custom Searchable Dropdown Combobox Handlers
    function openBillerDropdown() {
        const menu = document.getElementById('biller-dropdown-menu');
        const chevron = document.getElementById('biller-dropdown-chevron');
        if (!menu) return;
        menu.classList.remove('hidden');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
        const searchInput = document.getElementById('biller-search-filter');
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 50);
        }
    }

    function closeBillerDropdown() {
        const menu = document.getElementById('biller-dropdown-menu');
        const chevron = document.getElementById('biller-dropdown-chevron');
        if (!menu) return;
        menu.classList.add('hidden');
        if (chevron) chevron.style.transform = 'rotate(0deg)';
    }

    function toggleBillerDropdown(event) {
        if (event) event.stopPropagation();
        const menu = document.getElementById('biller-dropdown-menu');
        if (!menu) return;
        if (menu.classList.contains('hidden')) {
            openBillerDropdown();
        } else {
            closeBillerDropdown();
        }
    }

    function filterCustomBillerDropdown(query) {
        const q = (query || '').toLowerCase().trim();
        const container = document.getElementById('biller-items-container');
        if (!container) return;

        let matchCount = 0;
        const groups = container.querySelectorAll('.biller-group-section');
        groups.forEach(group => {
            let groupHasMatch = false;
            const items = group.querySelectorAll('.biller-option-item');
            items.forEach(item => {
                const name = (item.getAttribute('data-name') || item.innerText).toLowerCase();
                if (!q || name.includes(q)) {
                    item.classList.remove('hidden');
                    groupHasMatch = true;
                    matchCount++;
                } else {
                    item.classList.add('hidden');
                }
            });
            if (groupHasMatch) {
                group.classList.remove('hidden');
            } else {
                group.classList.add('hidden');
            }
        });

        const hint = document.getElementById('biller-hint');
        if (hint) {
            if (q) {
                hint.innerText = `Ditemukan ${matchCount} pilihan cocok untuk "${query}". Klik untuk memilih.`;
            } else {
                hint.innerText = '{{ $service->slug === "pdam-nusantara" ? "Klik kolom diatas untuk mencari wilayah" : "Klik kolom diatas untuk mencari provider" }}';
            }
        }
    }

    let selectedServiceOptionId = null;
    let selectedProductId = null;

    function selectCustomBillerOption(optionId, productId, name, adminFee, sku, event) {
        if (event) {
            event.stopPropagation();
        }

        const displayInput = document.getElementById('biller-combobox-input');
        if (displayInput) displayInput.value = name;

        const nativeSelect = document.getElementById('select-biller-product');
        if (nativeSelect) {
            nativeSelect.value = optionId || productId;
        }

        const allItems = document.querySelectorAll('.biller-option-item');
        allItems.forEach(item => {
            const matches = (optionId && item.getAttribute('data-id') == optionId) || (!optionId && item.getAttribute('data-product-id') == productId);
            if (matches) {
                item.classList.add('bg-[#525A43]', 'text-white');
                item.querySelector('.material-symbols-outlined')?.classList.remove('opacity-0');
            } else {
                item.classList.remove('bg-[#525A43]', 'text-white');
                item.querySelector('.material-symbols-outlined')?.classList.add('opacity-0');
            }
        });

        updateBillerSelection(optionId, productId, name, adminFee, sku);
        closeBillerDropdown();
    }

    function updateBillerSelection(optionId, productId, name, adminFee, sku) {
        selectedServiceOptionId = optionId;
        selectedProductId = productId;
        selectedProviderSku = sku;

        const optIdEl = document.getElementById('selected-service-option-id');
        if (optIdEl) optIdEl.value = optionId || '';

        const prodIdEl = document.getElementById('selected-product-id');
        if (prodIdEl) prodIdEl.value = productId || '';

        document.getElementById('summary-product-name').innerText = name;

        const regionDisplay = document.getElementById('bill-region-display');
        if (regionDisplay) regionDisplay.innerText = name;

        const headerEl = document.getElementById('selected-biller-header');
        if (headerEl) headerEl.innerText = name;

        currentFee = parseFloat(adminFee || 2500);
        const feeEl = document.getElementById('bill-fee-display');
        if (feeEl) feeEl.innerText = 'Rp ' + currentFee.toLocaleString('id-ID');

        const summaryFeeEl = document.getElementById('summary-admin-fee');
        if (summaryFeeEl) summaryFeeEl.innerText = 'Rp ' + currentFee.toLocaleString('id-ID');

        // Reset inquiry state on biller change so customer must deliberately click "Cek Tagihan" with the newly selected biller
        currentBillAmount = 0;
        currentPrice = currentFee;
        document.getElementById('selected-bill-amount').value = 0;
        
        const detailsContainer = document.getElementById('bill-details-container');
        if (detailsContainer) detailsContainer.classList.add('hidden');
        
        const errorBox = document.getElementById('bill-error-box');
        if (errorBox) errorBox.classList.add('hidden');

        const btnText = document.getElementById('btn-validate-text');
        if (btnText) btnText.innerText = 'Cek Tagihan Sekarang';

        const btnIcon = document.getElementById('btn-validate-icon');
        if (btnIcon) {
            btnIcon.innerText = 'receipt_long';
            btnIcon.classList.remove('animate-spin');
        }

        recalculateSummary();
        checkPpobFormValidity();
    }

    // Native select handler fallback
    function onSelectBillerProduct(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;

        const id = opt.value;
        const productId = opt.getAttribute('data-product-id') || id;
        const name = opt.getAttribute('data-name') || opt.text;
        const adminFee = parseFloat(opt.getAttribute('data-admin') || 2500);
        const sku = opt.getAttribute('data-sku') || '';

        const displayInput = document.getElementById('biller-combobox-input');
        if (displayInput) displayInput.value = name;

        updateBillerSelection(id, productId, name, adminFee, sku);
        closeBillerDropdown();
    }

    // Close dropdown on click outside
    document.addEventListener('click', function(e) {
        const combobox = document.getElementById('custom-biller-combobox');
        if (combobox && !combobox.contains(e.target)) {
            closeBillerDropdown();
        }
    });

    // STEP 3: Handle Cek Tagihan Click for Pure Bill (PDAM & Internet)
    async function handleCheckBillClick() {
        const target = document.getElementById('input-target').value.trim();
        const errorBox = document.getElementById('bill-error-box');
        const errorMessage = document.getElementById('bill-error-message');
        const detailsContainer = document.getElementById('bill-details-container');
        const btn = document.getElementById('btn-validate');
        const btnText = document.getElementById('btn-validate-text');
        const btnIcon = document.getElementById('btn-validate-icon');

        // 1. Wajib ada ID Pelanggan
        if (!target) {
            if (detailsContainer) detailsContainer.classList.add('hidden');
            if (errorBox) {
                errorBox.classList.remove('hidden');
                errorMessage.innerText = 'Silakan masukkan Nomor Sambungan / ID Pelanggan terlebih dahulu pada Langkah 1.';
            }
            document.getElementById('input-target').focus();
            return;
        }

        // 2. Wajib ada Wilayah / Provider yang dipilih pembeli
        if (!selectedProviderSku && !selectedServiceOptionId) {
            if (detailsContainer) detailsContainer.classList.add('hidden');
            if (errorBox) {
                errorBox.classList.remove('hidden');
                errorMessage.innerText = '{{ $service->slug === "pdam-nusantara" ? "Silakan pilih wilayah PDAM Anda terlebih dahulu pada Langkah 2." : "Silakan pilih provider internet Anda terlebih dahulu pada Langkah 2." }}';
            }
            openBillerDropdown();
            return;
        }

        // 3. Kirim data asli pilihan pembeli ke backend
        if (errorBox) errorBox.classList.add('hidden');
        if (btn) btn.disabled = true;
        if (btnText) btnText.innerText = 'Sedang Memeriksa Tagihan ke Biller...';
        if (btnIcon) {
            btnIcon.innerText = 'sync';
            btnIcon.classList.add('animate-spin');
        }

        try {
            const payload = {
                customer_number: target,
                service_type: '{{ $service->slug }}'
            };
            if (selectedProductId) payload.product_id = selectedProductId;
            if (selectedServiceOptionId) payload.service_option_id = selectedServiceOptionId;
            if (selectedProviderSku) payload.region = selectedProviderSku;

            const res = await fetch('{{ route("api.inquiry-ppob") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (btn) btn.disabled = false;
            if (btnIcon) btnIcon.classList.remove('animate-spin');

            if (res.ok && data.status === 'success' && data.bill_amount !== undefined) {
                // Success: Tampilkan hasil asli dari provider
                currentBillAmount = parseFloat(data.bill_amount || 0);
                const adminFee = parseFloat(data.admin_fee || currentFee || 2500);
                currentFee = adminFee;
                currentPrice = currentBillAmount + currentFee;

                document.getElementById('selected-bill-amount').value = currentBillAmount;

                const name = data.customer_name || 'Pelanggan Terdaftar';
                document.getElementById('bill-customer-name-verified').innerText = name;
                document.getElementById('bill-customer-name').innerText = name;
                document.getElementById('bill-target-display').innerText = target;
                document.getElementById('bill-period-display').innerText = data.period || '-';
                document.getElementById('bill-base-display').innerText = 'Rp ' + currentBillAmount.toLocaleString('id-ID');
                document.getElementById('bill-fee-display').innerText = 'Rp ' + currentFee.toLocaleString('id-ID');
                document.getElementById('bill-total-display').innerText = 'Rp ' + (currentBillAmount + currentFee).toLocaleString('id-ID');

                document.getElementById('summary-nickname').innerText = name;
                document.getElementById('validated-nickname').value = name;

                if (detailsContainer) detailsContainer.classList.remove('hidden');
                if (errorBox) errorBox.classList.add('hidden');
                if (btnText) btnText.innerText = '✓ Tagihan Terverifikasi (Klik untuk Cek Ulang)';
                if (btnIcon) btnIcon.innerText = 'check_circle';

                recalculateSummary();
                checkPpobFormValidity();
            } else {
                // Failure: Tampilkan error asli provider secara transparan, TANPA DATA DUMMY
                currentBillAmount = 0;
                document.getElementById('selected-bill-amount').value = 0;
                currentPrice = currentFee;

                if (detailsContainer) detailsContainer.classList.add('hidden');
                if (errorBox) {
                    errorBox.classList.remove('hidden');
                    errorMessage.innerText = data.message || 'Tagihan tidak ditemukan atau ID pelanggan tidak terdaftar pada biller tersebut.';
                }
                if (btnText) btnText.innerText = 'Cek Tagihan Sekarang';
                if (btnIcon) btnIcon.innerText = 'receipt_long';

                document.getElementById('summary-nickname').innerText = '-';
                document.getElementById('validated-nickname').value = '';

                recalculateSummary();
                checkPpobFormValidity();
            }
        } catch (err) {
            if (btn) btn.disabled = false;
            if (btnIcon) {
                btnIcon.classList.remove('animate-spin');
                btnIcon.innerText = 'receipt_long';
            }
            if (btnText) btnText.innerText = 'Cek Tagihan Sekarang';
            if (detailsContainer) detailsContainer.classList.add('hidden');
            if (errorBox) {
                errorBox.classList.remove('hidden');
                errorMessage.innerText = 'Gagal menghubungi server untuk memeriksa tagihan. Periksa koneksi internet Anda.';
            }
            recalculateSummary();
            checkPpobFormValidity();
        }
    }

    // Handle Cek Tagihan / ID for PLN
    async function handlePlnCheckClick() {
        const target = document.getElementById('input-target').value.trim();
        const btnText = document.getElementById('btn-validate-text');
        
        if (!target) {
            alert('Silakan masukkan Nomor Meter / ID Pelanggan PLN terlebih dahulu.');
            document.getElementById('input-target').focus();
            return;
        }

        if (btnText) btnText.innerText = 'Memeriksa...';

        try {
            const serviceType = (plnSelectedType === 'pascabayar') ? 'pln-pascabayar' : 'pln';
            const res = await fetch('{{ route("api.inquiry-ppob") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    service_type: serviceType,
                    customer_number: target
                })
            });

            const data = await res.json();
            if (btnText) btnText.innerText = 'Cek Tagihan / ID';

            if (res.ok && data.status === 'success') {
                const nickname = data.nickname || data.customer_name || 'PELANGGAN TERVERIFIKASI';
                const verifiedBox = document.getElementById('verified-box');
                if (verifiedBox) verifiedBox.classList.remove('hidden');
                document.getElementById('customer-nickname').innerText = nickname;
                document.getElementById('summary-nickname').innerText = nickname;
                document.getElementById('validated-nickname').value = nickname;

                if (plnSelectedType === 'pascabayar' && data.bill_amount > 0) {
                    currentBillAmount = parseFloat(data.bill_amount);
                    const currentAdmin = parseFloat(data.admin_fee || 2500);
                    
                    const plnNameEl = document.getElementById('pln-postpaid-name');
                    if (plnNameEl) plnNameEl.innerText = data.customer_name;

                    const plnBaseEl = document.getElementById('pln-postpaid-base');
                    if (plnBaseEl) plnBaseEl.innerText = 'Rp ' + currentBillAmount.toLocaleString('id-ID');

                    const plnFeeEl = document.getElementById('pln-postpaid-fee');
                    if (plnFeeEl) plnFeeEl.innerText = 'Rp ' + currentAdmin.toLocaleString('id-ID');

                    const plnTotalEl = document.getElementById('pln-postpaid-total');
                    if (plnTotalEl) plnTotalEl.innerText = 'Rp ' + (currentBillAmount + currentAdmin).toLocaleString('id-ID');

                    currentPrice = currentBillAmount + currentAdmin;
                    document.getElementById('selected-bill-amount').value = currentBillAmount;
                    recalculateSummary();
                }
                checkPpobFormValidity();
            } else {
                alert(data.message || 'Pemeriksaan ID Pelanggan PLN gagal.');
                checkPpobFormValidity();
            }
        } catch (e) {
            if (btnText) btnText.innerText = 'Cek Tagihan / ID';
            alert('Gagal menghubungi server untuk verifikasi ID.');
        }
    }

    // Voucher validation
    document.getElementById('btn-apply-voucher').addEventListener('click', async function() {
        const code = document.getElementById('voucher-input').value.trim();
        const statusEl = document.getElementById('voucher-status-text');

        if (!code) {
            alert('Masukkan kode voucher');
            return;
        }

        try {
            const res = await fetch('{{ route("api.validate-voucher") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    code: code,
                    amount: currentPrice
                })
            });

            const data = await res.json();

            if (data.status === 'success') {
                currentDiscount = data.discount_amount;
                document.getElementById('applied-voucher-code').value = code;
                statusEl.classList.remove('hidden', 'text-rose-700');
                statusEl.classList.add('text-emerald-700');
                statusEl.innerText = '✓ Voucher ' + code + ' berhasil diterapkan! Diskon: Rp ' + currentDiscount.toLocaleString('id-ID');
                recalculateSummary();
            } else {
                currentDiscount = 0;
                document.getElementById('applied-voucher-code').value = '';
                statusEl.classList.remove('hidden', 'text-emerald-700');
                statusEl.classList.add('text-rose-700');
                statusEl.innerText = '✕ ' + data.message;
                recalculateSummary();
            }
        } catch (e) {
            alert('Terjadi kesalahan saat memvalidasi voucher');
        }
    });

    // Real-Time Form Validation & Checkout Button Activator
    function checkPpobFormValidity() {
        const target = document.getElementById('input-target')?.value.trim();
        const productId = document.getElementById('selected-product-id')?.value;
        const paymentMethod = document.getElementById('selected-payment-method')?.value;
        const phone = document.querySelector('input[name="customer_phone"]')?.value.trim() || '';
        const email = document.querySelector('input[name="customer_email"]')?.value.trim() || '';

        // Phone min 9 digits OR valid email
        const phoneDigits = phone.replace(/[^0-9]/g, '');
        const phoneValid = phoneDigits.length >= 9;
        const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        const contactValid = (phoneValid || emailValid);

        // For bill mode (PDAM, Telkom, PLN Pascabayar), bill MUST be checked (currentBillAmount > 0)
        let billValid = true;
        if (isBillMode) {
            billValid = (currentBillAmount > 0);
        }

        const isValid = Boolean(target && productId && paymentMethod && contactValid && billValid);

        const btn = document.getElementById('btn-submit-order');
        const warning = document.getElementById('form-validation-warning');

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
                        warning.innerText = '⚠️ Silakan masukkan nomor tujuan / ID Pelanggan terlebih dahulu.';
                    } else if (!productId) {
                        warning.innerText = '{{ $service->slug === "pdam-nusantara" ? "⚠️ Silakan pilih wilayah PDAM terlebih dahulu." : ($service->slug === "telkom-indihome" ? "⚠️ Silakan pilih provider internet terlebih dahulu." : "⚠️ Silakan pilih produk / nominal terlebih dahulu.") }}';
                    } else if (isBillMode && currentBillAmount <= 0) {
                        warning.innerText = '⚠️ Silakan klik tombol "Cek Tagihan Sekarang" untuk melihat rincian tagihan resmi sebelum membayar.';
                    } else if (!contactValid) {
                        warning.innerText = '⚠️ Silakan isi Nomor WhatsApp (min 9 digit) atau Email yang valid untuk menerima bukti invoice.';
                    } else {
                        warning.innerText = '⚠️ Lengkapi data pemesanan di atas untuk mengaktifkan tombol bayar.';
                    }
                }
            }
        }
    }

    // Initial on page load
    document.addEventListener('DOMContentLoaded', function() {
        recalculateSummary();
        checkPpobFormValidity();
    });
</script>
@endpush
@endsection
