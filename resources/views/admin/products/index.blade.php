@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-12">
    
    <!-- Page Header & Actions -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-[#525A43] animate-pulse"></span>
                <span class="text-xs font-bold uppercase tracking-widest text-[#5C6454]">Katalog &amp; Manajemen Laba Real-Time</span>
            </div>
            <h1 class="text-2xl lg:text-3xl font-extrabold text-[#1A2016] tracking-tight mt-0.5">Katalog Produk &amp; Manajemen Laba</h1>
            <p class="text-xs text-[#5C6454] mt-1">
                Kolom harga modal terisi otomatis secara real-time dari provider. Kelola harga jual dan pantau margin laba bersih per kategori secara instan.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Search Input Form -->
            <form action="{{ route('admin.products') }}" method="GET" class="relative">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari SKU atau nama..." 
                    class="h-10 bg-[#FFFFFF] border border-[#DCD1C2] px-3.5 pl-9 rounded-xl text-xs font-semibold text-[#1A2016] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43]"
                >
                <span class="material-symbols-outlined absolute left-2.5 top-2.5 text-[#878c7f] text-base">search</span>
            </form>

            <!-- Real-Time Provider Sync Button -->
            <form action="{{ route('admin.products.sync-digiflazz') }}" method="POST" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Sinkronisasi...';">
                @csrf
                <button 
                    type="submit" 
                    class="h-10 px-4 bg-[#1F2419] hover:bg-[#3B432D] text-white rounded-xl text-xs font-extrabold flex items-center gap-1.5 shadow-sm transition-all cursor-pointer border border-[#525A43]"
                    title="Tarik &amp; Update Harga Modal Real-Time dari Provider"
                >
                    <span class="material-symbols-outlined text-base text-emerald-400">sync</span>
                    <span>Tarik Data Provider (Real-Time)</span>
                </button>
            </form>

            <!-- Bulk Margin Adjuster Button -->
            <button 
                type="button" 
                onclick="openBulkMarginModal()"
                class="h-10 px-3.5 bg-[#F4EFE6] hover:bg-[#EAE1D4] text-[#1A2016] border border-[#DCD1C2] rounded-xl text-xs font-extrabold flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer"
                title="Atur margin persentase massal untuk game ini"
            >
                <span class="material-symbols-outlined text-base text-[#525A43]">tune</span>
                <span>Set Margin Massal</span>
            </button>

            <!-- Save All Changes Button -->
            <button 
                type="button" 
                id="btn-save-all-changes"
                onclick="saveAllChangedProducts()"
                class="h-10 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-extrabold flex items-center gap-2 shadow-sm transition-all cursor-pointer opacity-50 pointer-events-none"
                title="Simpan semua produk yang mengalami perubahan"
                disabled
            >
                <span class="material-symbols-outlined text-base">save_as</span>
                <span id="btn-save-all-text">Simpan Semua Perubahan (0)</span>
            </button>
        </div>
    </div>

    @php
        $getGameIcon = function($slug) {
            return match($slug) {
                'mobile-legends' => ['icon' => 'workspace_premium', 'color' => 'text-amber-700', 'active_color' => 'text-amber-300'],
                'free-fire' => ['icon' => 'local_fire_department', 'color' => 'text-orange-600', 'active_color' => 'text-orange-300'],
                'free-fire-max' => ['icon' => 'military_tech', 'color' => 'text-amber-600', 'active_color' => 'text-amber-300'],
                'pubg-mobile' => ['icon' => 'target', 'color' => 'text-teal-700', 'active_color' => 'text-teal-300'],
                'call-of-duty-mobile' => ['icon' => 'crosshair', 'color' => 'text-emerald-700', 'active_color' => 'text-emerald-300'],
                'honor-of-kings' => ['icon' => 'crown', 'color' => 'text-indigo-600', 'active_color' => 'text-indigo-300'],
                'magic-chess-go-go' => ['icon' => 'chess', 'color' => 'text-violet-600', 'active_color' => 'text-violet-300'],
                'valorant' => ['icon' => 'shield', 'color' => 'text-rose-600', 'active_color' => 'text-rose-300'],
                'pln' => ['icon' => 'bolt', 'color' => 'text-amber-600', 'active_color' => 'text-amber-300'],
                'telkomsel' => ['icon' => 'phone_iphone', 'color' => 'text-red-600', 'active_color' => 'text-red-300'],
                'indosat' => ['icon' => 'cell_tower', 'color' => 'text-amber-600', 'active_color' => 'text-amber-300'],
                'xl' => ['icon' => 'cell_tower', 'color' => 'text-blue-600', 'active_color' => 'text-blue-300'],
                'axis' => ['icon' => 'wifi_tethering', 'color' => 'text-purple-600', 'active_color' => 'text-purple-300'],
                'tri' => ['icon' => 'signal_cellular_alt', 'color' => 'text-orange-600', 'active_color' => 'text-orange-300'],
                'smartfren' => ['icon' => 'network_check', 'color' => 'text-pink-600', 'active_color' => 'text-pink-300'],
                'byu' => ['icon' => 'sim_card', 'color' => 'text-cyan-600', 'active_color' => 'text-cyan-300'],
                'pln-pascabayar' => ['icon' => 'receipt_long', 'color' => 'text-amber-700', 'active_color' => 'text-amber-300'],
                'pdam-nusantara' => ['icon' => 'water_drop', 'color' => 'text-cyan-700', 'active_color' => 'text-cyan-300'],
                'telkom-indihome' => ['icon' => 'router', 'color' => 'text-red-700', 'active_color' => 'text-red-300'],
                default => ['icon' => 'sports_esports', 'color' => 'text-[#525A43]', 'active_color' => 'text-white'],
            };
        };
        $activeIconInfo = $getGameIcon($activeTab);
    @endphp

    <!-- Category / Game Tabs Navigation -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-3 shadow-sm space-y-2">
        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none">
            
            <div class="flex items-center gap-1.5 pr-2 border-r border-[#DCD1C2]">
                <span class="text-[10px] uppercase font-bold text-[#5C6454] tracking-wider px-2">Games:</span>
                
                @foreach($games->where('category.type', 'game') as $g)
                    @php 
                        $iconData = $getGameIcon($g->slug); 
                        $cnt = $unreviewedCountsByGame[$g->id] ?? 0;
                    @endphp
                    <a 
                        href="{{ route('admin.products', ['tab' => $g->slug]) }}" 
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all {{ $activeTab === $g->slug ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#1A2016] hover:bg-[#EAE1D4]' }}"
                    >
                        <span class="material-symbols-outlined text-sm {{ $activeTab === $g->slug ? 'text-white' : $iconData['color'] }}">{{ $iconData['icon'] }}</span>
                        <span>{{ $g->name }}</span>
                        @if($cnt > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black {{ $activeTab === $g->slug ? 'bg-amber-400 text-amber-950' : 'bg-rose-500 text-white' }} shadow-xs animate-pulse">
                                {{ $cnt }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-1.5 pl-1 pr-2 border-r border-[#DCD1C2]">
                <span class="text-[10px] uppercase font-bold text-[#5C6454] tracking-wider px-2">Pulsa &amp; Operator:</span>

                @foreach($games->where('category.slug', 'pulsa-all-operator') as $p)
                    @php 
                        $iconData = $getGameIcon($p->slug); 
                        $cnt = $unreviewedCountsByGame[$p->id] ?? 0;
                    @endphp
                    <a 
                        href="{{ route('admin.products', ['tab' => $p->slug]) }}" 
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all {{ $activeTab === $p->slug ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#1A2016] hover:bg-[#EAE1D4]' }}"
                    >
                        <span class="material-symbols-outlined text-sm {{ $activeTab === $p->slug ? 'text-white' : $iconData['color'] }}">{{ $iconData['icon'] }}</span>
                        <span>{{ $p->name }}</span>
                        @if($cnt > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black {{ $activeTab === $p->slug ? 'bg-amber-400 text-amber-950' : 'bg-rose-500 text-white' }} shadow-xs animate-pulse">
                                {{ $cnt }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-1.5 pl-1 pr-2 border-r border-[#DCD1C2]">
                <span class="text-[10px] uppercase font-bold text-[#5C6454] tracking-wider px-2">Listrik PLN:</span>

                @foreach($games->where('category.slug', 'pln') as $l)
                    @php 
                        $iconData = $getGameIcon($l->slug); 
                        $cnt = $unreviewedCountsByGame[$l->id] ?? 0;
                    @endphp
                    <a 
                        href="{{ route('admin.products', ['tab' => $l->slug]) }}" 
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all {{ $activeTab === $l->slug ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#1A2016] hover:bg-[#EAE1D4]' }}"
                    >
                        <span class="material-symbols-outlined text-sm {{ $activeTab === $l->slug ? 'text-white' : $iconData['color'] }}">{{ $iconData['icon'] }}</span>
                        <span>{{ $l->name }}</span>
                        @if($cnt > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black {{ $activeTab === $l->slug ? 'bg-amber-400 text-amber-950' : 'bg-rose-500 text-white' }} shadow-xs animate-pulse">
                                {{ $cnt }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-1.5 pl-1">
                <span class="text-[10px] uppercase font-bold text-[#5C6454] tracking-wider px-2">Pascabayar:</span>

                @foreach($games->where('category.slug', 'tagihan-pascabayar') as $pb)
                    @php 
                        $iconData = $getGameIcon($pb->slug); 
                        $cnt = $unreviewedCountsByGame[$pb->id] ?? 0;
                    @endphp
                    <a 
                        href="{{ route('admin.products', ['tab' => $pb->slug]) }}" 
                        class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all {{ $activeTab === $pb->slug ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#1A2016] hover:bg-[#EAE1D4]' }}"
                    >
                        <span class="material-symbols-outlined text-sm {{ $activeTab === $pb->slug ? 'text-white' : $iconData['color'] }}">{{ $iconData['icon'] }}</span>
                        <span>{{ $pb->name }}</span>
                        @if($cnt > 0)
                            <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black {{ $activeTab === $pb->slug ? 'bg-amber-400 text-amber-950' : 'bg-rose-500 text-white' }} shadow-xs animate-pulse">
                                {{ $cnt }}
                            </span>
                        @endif
                    </a>
                @endforeach

                <!-- Semua Item Tab -->
                <a 
                    href="{{ route('admin.products', ['tab' => 'all']) }}" 
                    class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold whitespace-nowrap transition-all ml-2 {{ $activeTab === 'all' ? 'bg-[#525A43] text-white shadow-xs' : 'bg-[#F6F0E8] text-[#5C6454] hover:bg-[#EAE1D4] hover:text-[#1A2016]' }}"
                >
                    <span class="material-symbols-outlined text-sm">apps</span>
                    <span>Semua Global</span>
                    @if(($totalUnreviewedCount ?? 0) > 0)
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-black {{ $activeTab === 'all' ? 'bg-amber-400 text-amber-950' : 'bg-rose-500 text-white' }} shadow-xs animate-pulse">
                            {{ $totalUnreviewedCount }}
                        </span>
                    @endif
                </a>
            </div>

        </div>
    </div>

    <!-- Active Tab Telemetry Banner -->
    <div class="bg-[#F4EFE6] border border-[#DCD1C2] rounded-3xl p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-[#525A43] text-white flex items-center justify-center shrink-0 shadow-xs">
                <span class="material-symbols-outlined text-2xl {{ $activeIconInfo['active_color'] }}">{{ $activeIconInfo['icon'] }}</span>
            </div>
            <div class="flex flex-col">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#5C6454]">Kategori Terpilih</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Real-Time Gateway Active
                    </span>
                </div>
                <h2 class="text-lg font-extrabold text-[#1A2016]">
                    {{ $activeGame->name ?? 'Semua Katalog Produk' }}
                </h2>
                @if($activeGame)
                    <span class="text-[11px] text-[#5C6454] font-medium">Provider: {{ $activeGame->publisher }} • Sub-kategori: <strong>{{ implode(', ', $presetSubCategories[$activeTab] ?? ['Top Up']) }}</strong></span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="bg-[#FFFFFF] border border-[#DCD1C2] px-4 py-2 rounded-2xl text-center shadow-xs">
                <span class="text-[10px] text-[#5C6454] uppercase font-bold">Total Menu / SKU</span>
                <p class="text-base font-extrabold text-[#1A2016]">{{ $tabTotalItems }} Item</p>
            </div>
            <div class="bg-[#FFFFFF] border border-[#DCD1C2] px-4 py-2 rounded-2xl text-center shadow-xs">
                <span class="text-[10px] text-[#5C6454] uppercase font-bold">Rata-rata Laba / Item</span>
                <p class="text-base font-extrabold text-emerald-800">+ Rp {{ number_format($tabAvgProfit, 0, ',', '.') }}</p>
            </div>
            @if($lastSyncedAt)
                <div class="hidden md:block bg-[#FFFFFF] border border-[#DCD1C2] px-4 py-2 rounded-2xl text-center shadow-xs">
                    <span class="text-[10px] text-[#5C6454] uppercase font-bold">Sinkronisasi Terakhir</span>
                    <p class="text-xs font-bold text-[#1A2016] mt-0.5">{{ \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Dedicated Sync Changes & Price Review Panel (Fitur Tempat Sendiri per Kategori) -->
    @if(isset($unreviewedChanges) && $unreviewedChanges->count() > 0)
        <div class="bg-gradient-to-br from-amber-50/90 via-[#FFFFFF] to-[#FDFBF7] border-2 border-amber-300/80 rounded-3xl p-5 lg:p-7 shadow-md relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-amber-200/30 rounded-full blur-2xl pointer-events-none"></div>

            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 mb-4 border-b border-amber-200/70">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                        <span class="material-symbols-outlined text-2xl">published_with_changes</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-extrabold text-[#1A2016]">Pusat Perubahan Data &amp; Produk Baru</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-500 text-white shadow-xs animate-pulse">
                                {{ $unreviewedChanges->count() }} Perubahan Terdeteksi
                            </span>
                        </div>
                        <p class="text-xs text-[#5C6454] mt-0.5">
                            Data berikut baru ditarik dari provider untuk kategori <strong>{{ $activeGame->name ?? 'Layanan Aktif' }}</strong>. Anda dapat langsung meninjau produk baru, memeriksa kenaikan/penurunan harga modal, dan menyesuaikan harga jual serta margin laba di sini.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <form action="{{ route('admin.products.changes.mark-all-reviewed') }}" method="POST" onsubmit="return confirm('Tandai semua {{ $unreviewedChanges->count() }} perubahan di kategori ini sebagai selesai diperiksa?');">
                        @csrf
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        @if($activeGame)
                            <input type="hidden" name="game_id" value="{{ $activeGame->id }}">
                        @elseif($activeTab === 'ppob' && $ppobCategory)
                            <input type="hidden" name="category_id" value="{{ $ppobCategory->id }}">
                        @endif
                        <button type="submit" class="h-9 px-3.5 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-extrabold flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer">
                            <span class="material-symbols-outlined text-base">done_all</span>
                            <span>Tandai Semua Selesai Diperiksa</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table of Changes -->
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="text-[10px] text-[#5C6454] uppercase font-extrabold border-b border-amber-200">
                            <th class="pb-3 pr-2">Status Provider</th>
                            <th class="pb-3 px-2">Produk &amp; SKU</th>
                            <th class="pb-3 px-2">Harga Modal Provider</th>
                            <th class="pb-3 px-2">Harga Jual Saat Ini</th>
                            <th class="pb-3 px-2">Penyesuaian Harga Jual &amp; Laba</th>
                            <th class="pb-3 pl-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-200/60">
                        @foreach($unreviewedChanges as $change)
                            @php
                                $p = $change->product;
                                $costDiff = $change->old_cost_price ? ($change->new_cost_price - $change->old_cost_price) : 0;
                            @endphp
                            <tr class="hover:bg-amber-100/40 transition-colors">
                                <!-- Type Badge -->
                                <td class="py-3 pr-2 whitespace-nowrap">
                                    @if($change->change_type === 'new_product')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            <span class="material-symbols-outlined text-xs">fiber_new</span>
                                            PRODUK BARU
                                        </span>
                                    @elseif($change->change_type === 'price_changed')
                                        @if($costDiff > 0)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300">
                                                <span class="material-symbols-outlined text-xs">trending_up</span>
                                                MODAL NAIK
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black bg-cyan-100 text-cyan-800 border border-cyan-300">
                                                <span class="material-symbols-outlined text-xs">trending_down</span>
                                                MODAL TURUN
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-300">
                                            <span class="material-symbols-outlined text-xs">sync_alt</span>
                                            STATUS BERUBAH
                                        </span>
                                    @endif
                                </td>

                                <!-- Product Info -->
                                <td class="py-3 px-2">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-[#1A2016]">{{ $p?->name ?? 'Produk ID #'.$change->product_id }}</span>
                                        <span class="text-[10px] font-mono text-[#878c7f]">SKU: {{ $p?->provider_sku ?? $p?->sku }}</span>
                                    </div>
                                </td>

                                <!-- Cost Comparison -->
                                <td class="py-3 px-2 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-extrabold text-[#1A2016]">Rp {{ number_format($change->new_cost_price, 0, ',', '.') }}</span>
                                        @if($change->old_cost_price)
                                            <div class="flex items-center gap-1 text-[10px]">
                                                <span class="line-through text-[#878c7f]">Rp {{ number_format($change->old_cost_price, 0, ',', '.') }}</span>
                                                <span class="font-bold {{ $costDiff > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                                    ({{ $costDiff > 0 ? '+' : '' }}Rp {{ number_format($costDiff, 0, ',', '.') }})
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-[10px] text-emerald-700 font-bold">Produk Baru Ditambahkan</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Current Selling Price -->
                                <td class="py-3 px-2 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-[#1A2016]">Rp {{ number_format($p?->selling_price ?? $change->new_selling_price, 0, ',', '.') }}</span>
                                        @php
                                            $currProfit = ($p?->selling_price ?? $change->new_selling_price) - $change->new_cost_price;
                                        @endphp
                                        <span class="text-[10px] font-bold {{ $currProfit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                            Laba: Rp {{ number_format($currProfit, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Quick Adjustment Form -->
                                <td class="py-3 px-2">
                                    <form action="{{ route('admin.products.changes.review', $change->id) }}" method="POST" class="flex items-center gap-2" id="form-change-{{ $change->id }}">
                                        @csrf
                                        <div class="relative w-32">
                                            <span class="absolute left-2.5 top-2 text-[10px] font-bold text-[#878c7f]">Rp</span>
                                            <input 
                                                type="number" 
                                                name="selling_price" 
                                                value="{{ (int) ($p?->selling_price ?? $change->new_selling_price) }}" 
                                                class="w-full h-8 pl-8 pr-2 bg-white border border-amber-300 rounded-lg text-xs font-bold text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                                                oninput="updateChangeProfit({{ $change->id }}, this.value, {{ $change->new_cost_price }})"
                                            >
                                        </div>
                                        <div class="text-[10px] whitespace-nowrap">
                                            <span class="text-[#5C6454]">Laba baru:</span>
                                            <strong id="change-profit-{{ $change->id }}" class="text-emerald-700 font-extrabold">
                                                Rp {{ number_format($currProfit, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                </td>

                                <!-- Action Buttons -->
                                <td class="py-3 pl-2 text-right whitespace-nowrap">
                                        <button type="submit" class="h-8 px-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-extrabold inline-flex items-center gap-1 shadow-xs transition-colors cursor-pointer">
                                            <span class="material-symbols-outlined text-sm">check</span>
                                            <span>Simpan &amp; Selesai</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif(isset($totalUnreviewedCount) && $totalUnreviewedCount > 0)
        <!-- Notice if other categories have pending changes -->
        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-2xl flex items-center justify-between text-xs shadow-xs">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-amber-600 text-lg">info</span>
                <span>Semua produk di tab ini sudah diperiksa. Terdapat <strong>{{ $totalUnreviewedCount }} perubahan produk</strong> di kategori lain yang perlu Anda periksa.</span>
            </div>
            <span class="text-[10px] text-amber-700 font-bold uppercase tracking-wider">Periksa Tab Kategori Lainnya (Berbadge Angka)</span>
        </div>
    @endif

    <!-- Products Table Card -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-5 lg:p-7 shadow-sm">
        
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#DCD1C2]/60">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-lg text-[#525A43]">table_chart</span>
                <h3 class="text-sm font-extrabold text-[#1A2016]">Daftar Item &amp; Penyesuaian Harga Modal / Jual</h3>
            </div>
            <span class="text-xs text-[#5C6454]">Menampilkan {{ $products->count() }} dari {{ $products->total() }} produk</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="text-[10px] text-[#5C6454] uppercase font-extrabold border-b border-[#DCD1C2]">
                        <th class="pb-3.5 pr-2">SKU Provider</th>
                        <th class="pb-3.5 px-2">Nama Menu / Produk</th>
                        <th class="pb-3.5 px-2">Sub-Kategori</th>
                        <th class="pb-3.5 px-2 bg-emerald-50/50 rounded-t-lg">
                            <div class="flex items-center gap-1 text-emerald-900">
                                <span class="material-symbols-outlined text-xs">bolt</span>
                                <span>Harga Modal (Rp)</span>
                            </div>
                        </th>
                        <th class="pb-3.5 px-2">Harga Jual (Rp)</th>
                        <th class="pb-3.5 px-2">Laba Bersih &amp; Margin</th>
                        <th class="pb-3.5 px-2">Badge</th>
                        <th class="pb-3.5 px-2">Status</th>
                        <th class="pb-3.5 pl-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#DCD1C2]/60">
                    @forelse($products as $prod)
                        @php
                            $isBillItem = ($prod->cost_price == 0 && ($prod->sub_category === 'Pascabayar' || $prod->sub_category === 'Bayar Tagihan' || str_contains($prod->sku, 'POSTPAID') || str_contains($prod->sku, 'BILL')));
                            $marginPct = $prod->cost_price > 0 ? round((($prod->selling_price - $prod->cost_price) / $prod->cost_price) * 100, 1) : 100;
                        @endphp
                        <tr class="hover:bg-[#FDFBF7] transition-all border-l-4 border-transparent" data-row-id="{{ $prod->id }}" id="row-{{ $prod->id }}">
                            
                            <!-- SKU -->
                            <td class="py-3.5 pr-2 font-mono whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-[#1A2016]">{{ $prod->provider_sku ?? $prod->sku }}</span>
                                    <span class="text-[9px] text-[#878c7f] font-sans">{{ $prod->sku }}</span>
                                </div>
                            </td>

                            <!-- Form Edit Row -->
                            <td class="py-3.5 px-2 max-w-[240px]">
                                <form action="{{ route('admin.products.update', $prod->id) }}" method="POST" id="form-update-{{ $prod->id }}" class="hidden">
                                    @csrf
                                </form>
                                <div class="flex flex-col">
                                    <input 
                                        type="text" 
                                        form="form-update-{{ $prod->id }}"
                                        name="name" 
                                        id="name-{{ $prod->id }}"
                                        value="{{ $prod->name }}" 
                                        oninput="markRowDirty({{ $prod->id }})"
                                        class="w-full h-8 bg-[#F6F0E8] border border-[#DCD1C2] px-2.5 rounded-lg text-xs font-bold text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                                        required
                                    >
                                    <span class="text-[10px] text-[#5C6454] mt-0.5">{{ $prod->game->name ?? $prod->category->name }}</span>
                                </div>
                            </td>

                            <!-- Sub Category -->
                            <td class="py-3.5 px-2 whitespace-nowrap">
                                <input 
                                    type="text" 
                                    form="form-update-{{ $prod->id }}"
                                    name="sub_category" 
                                    id="subcat-{{ $prod->id }}"
                                    value="{{ $prod->sub_category }}" 
                                    oninput="markRowDirty({{ $prod->id }})"
                                    placeholder="Sub Kategori" 
                                    class="w-36 h-8 bg-[#F6F0E8] border border-[#DCD1C2] px-2.5 rounded-lg text-xs font-bold text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                                >
                            </td>

                            <!-- Cost Price (Harga Modal Provider - Realtime) -->
                            <td class="py-3.5 px-2 whitespace-nowrap bg-emerald-50/40">
                                <div class="relative flex items-center">
                                    <span class="absolute left-2.5 text-[10px] font-bold text-emerald-800">Rp</span>
                                    <input 
                                        type="number" 
                                        form="form-update-{{ $prod->id }}"
                                        name="cost_price" 
                                        id="cost-{{ $prod->id }}"
                                        value="{{ (int)$prod->cost_price }}" 
                                        step="50"
                                        class="w-28 h-8 bg-white border border-emerald-300 pl-8 pr-2 rounded-lg text-xs font-extrabold text-emerald-950 focus:outline-none focus:border-emerald-600 shadow-2xs"
                                        oninput="recalculateRow({{ $prod->id }}); markRowDirty({{ $prod->id }});"
                                        required
                                    >
                                </div>
                            </td>

                            <!-- Selling Price -->
                            <td class="py-3.5 px-2 whitespace-nowrap">
                                <div class="relative flex items-center">
                                    <span class="absolute left-2.5 text-[10px] font-bold text-[#878c7f]">Rp</span>
                                    <input 
                                        type="number" 
                                        form="form-update-{{ $prod->id }}"
                                        name="selling_price" 
                                        id="sell-{{ $prod->id }}"
                                        value="{{ (int)$prod->selling_price }}" 
                                        step="50"
                                        class="w-28 h-8 bg-[#F6F0E8] border border-[#DCD1C2] pl-8 pr-2 rounded-lg text-xs font-extrabold text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                                        oninput="recalculateRow({{ $prod->id }}); markRowDirty({{ $prod->id }});"
                                        required
                                    >
                                </div>
                            </td>

                            <!-- Live Margin & Net Profit -->
                            <td class="py-3.5 px-2 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-extrabold text-emerald-800 text-xs" id="profit-display-{{ $prod->id }}">+ Rp {{ number_format($prod->profit, 0, ',', '.') }}</span>
                                    <span class="text-[10px] font-bold text-[#5C6454]" id="margin-display-{{ $prod->id }}">{{ $isBillItem ? 'Biaya Admin (Laba Bersih)' : $marginPct.'% margin' }}</span>
                                </div>
                            </td>

                            <!-- Badge Promo -->
                            <td class="py-3.5 px-2 whitespace-nowrap">
                                <input 
                                    type="text" 
                                    form="form-update-{{ $prod->id }}"
                                    name="badge" 
                                    id="badge-{{ $prod->id }}"
                                    value="{{ $prod->badge }}" 
                                    oninput="markRowDirty({{ $prod->id }})"
                                    placeholder="PROMO" 
                                    class="w-20 h-8 bg-[#F6F0E8] border border-[#DCD1C2] px-2 rounded-lg text-[10px] font-bold uppercase text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                                >
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-2 whitespace-nowrap">
                                <select 
                                    form="form-update-{{ $prod->id }}"
                                    name="status" 
                                    id="status-{{ $prod->id }}"
                                    onchange="markRowDirty({{ $prod->id }})"
                                    class="h-8 bg-[#F6F0E8] border border-[#DCD1C2] px-2 rounded-lg text-xs font-bold {{ $prod->status === 'active' ? 'text-emerald-800' : 'text-rose-800' }}"
                                >
                                    <option value="active" {{ $prod->status === 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ $prod->status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 pl-2 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Dynamic Save Button (Clean vs Dirty) -->
                                    <button 
                                        type="button" 
                                        id="btn-save-row-{{ $prod->id }}"
                                        onclick="saveSingleRow({{ $prod->id }})"
                                        class="btn-save-row px-2.5 py-1.5 bg-[#EAE1D4] hover:bg-[#DCD1C2] text-[#5C6454] rounded-xl font-bold text-xs transition-all shadow-2xs flex items-center gap-1 cursor-pointer"
                                        title="Status: Tersimpan (Klik untuk simpan ulang)"
                                    >
                                        <span class="material-symbols-outlined text-sm text-emerald-700" id="icon-save-{{ $prod->id }}">check_circle</span>
                                        <span id="label-save-{{ $prod->id }}">Tersimpan</span>
                                    </button>

                                    <form action="{{ route('admin.products.destroy', $prod->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk {{ $prod->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl transition-colors cursor-pointer"
                                            title="Hapus Produk"
                                        >
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-12 text-sm text-[#5C6454]">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-3xl text-[#878c7f]">inventory_2</span>
                                    <span>Belum ada produk aktif di kategori <strong>{{ $activeGame->name ?? $activeTab }}</strong>.</span>
                                    <form action="{{ route('admin.products.sync-digiflazz') }}" method="POST" class="mt-2">
                                        @csrf
                                        <button type="submit" class="px-4 py-2 bg-[#525A43] text-white rounded-xl text-xs font-bold shadow-xs cursor-pointer">
                                            Tarik Data Produk dari Provider Sekarang
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-5 border-t border-[#DCD1C2]/60 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span class="text-xs text-[#5C6454]">Menampilkan total {{ $products->total() }} produk aktif</span>
            <div>
                {{ $products->links() }}
            </div>
        </div>
    </div>

</div>

<!-- Modal Set Margin Massal -->
<div id="bulk-margin-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl space-y-5 animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between pb-3 border-b border-[#DCD1C2]/60">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-[#525A43] text-white flex items-center justify-center shadow-xs">
                    <span class="material-symbols-outlined text-xl">tune</span>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-[#1A2016]">Set Margin Laba Massal</h3>
                    <p class="text-xs text-[#5C6454]">Atur margin otomatis dari harga modal provider</p>
                </div>
            </div>
            <button type="button" onclick="closeBulkMarginModal()" class="w-8 h-8 rounded-full bg-[#F6F0E8] hover:bg-[#EAE1D4] text-[#1A2016] flex items-center justify-center cursor-pointer">
                ✕
            </button>
        </div>

        <form action="{{ route('admin.products.bulk-margin') }}" method="POST" class="space-y-4">
            @csrf

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-[#1A2016]">Target Kategori / Game:</label>
                <select name="game_id" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 rounded-xl text-xs font-bold text-[#1A2016] focus:outline-none focus:border-[#525A43]">
                    <option value="">Semua Game &amp; Layanan Provider</option>
                    @foreach($games as $g)
                        <option value="{{ $g->id }}" {{ ($activeGame && $activeGame->id == $g->id) ? 'selected' : '' }}>
                            {{ $g->name }} ({{ $g->category?->name }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-[#1A2016]">Tipe Margin:</label>
                    <select name="mode" id="bulk-margin-mode" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 rounded-xl text-xs font-bold text-[#1A2016] focus:outline-none focus:border-[#525A43]">
                        <option value="percent">Persentase (%)</option>
                        <option value="fixed">Nominal Tetap (Rp)</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-[#1A2016]">Besaran Margin:</label>
                    <input 
                        type="number" 
                        name="value" 
                        value="5" 
                        step="0.5"
                        min="0"
                        placeholder="Contoh: 5 (untuk 5%)" 
                        class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 rounded-xl text-xs font-extrabold text-[#1A2016] focus:outline-none focus:border-[#525A43]"
                        required
                    >
                </div>
            </div>

            <p class="text-[11px] text-[#5C6454] bg-[#F4EFE6] p-3 rounded-xl border border-[#DCD1C2]">
                💡 Harga jual akan otomatis dihitung: <strong>Harga Modal + Margin</strong>, dan dibulatkan rapi ke kelipatan Rp 50.
            </p>

            <div class="pt-3 border-t border-[#DCD1C2]/60 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeBulkMarginModal()" class="px-4 h-10 bg-[#F6F0E8] hover:bg-[#EAE1D4] text-[#1A2016] rounded-xl text-xs font-bold">
                    Batal
                </button>
                <button type="submit" class="px-5 h-10 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-extrabold flex items-center gap-1.5 shadow-sm">
                    <span class="material-symbols-outlined text-sm">check</span>
                    <span>Terapkan Margin</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Unsaved Changes Sticky Bar -->
<div id="floating-unsaved-bar" class="fixed bottom-6 inset-x-0 mx-auto max-w-xl bg-[#1A2016] text-white p-3.5 px-6 rounded-2xl shadow-2xl border border-amber-500/50 flex items-center justify-between gap-4 z-50 transform translate-y-32 transition-transform duration-300 pointer-events-none opacity-0">
    <div class="flex items-center gap-3">
        <span class="relative flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
        </span>
        <div class="flex flex-col">
            <span class="text-xs font-bold text-amber-300" id="floating-unsaved-count">Ada 0 perubahan yang belum disimpan!</span>
            <span class="text-[10px] text-[#DCD1C2]">Klik tombol untuk memperbarui database secara instan.</span>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button 
            type="button" 
            onclick="saveAllChangedProducts()" 
            id="btn-floating-save" 
            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-extrabold transition-all shadow-md flex items-center gap-1.5 cursor-pointer"
        >
            <span class="material-symbols-outlined text-sm">save_as</span>
            <span>Simpan Semua</span>
        </button>
    </div>
</div>

@push('scripts')
<script>
    const subCatMap = @json($presetSubCategories);
    const dirtyRows = new Set();

    function markRowDirty(id) {
        dirtyRows.add(id);
        const row = document.getElementById('row-' + id);
        if (row) {
            row.classList.add('bg-amber-50/70', '!border-amber-500');
            row.classList.remove('border-transparent');
        }

        const btn = document.getElementById('btn-save-row-' + id);
        const icon = document.getElementById('icon-save-' + id);
        const label = document.getElementById('label-save-' + id);

        if (btn && icon && label) {
            btn.classList.remove('bg-[#EAE1D4]', 'hover:bg-[#DCD1C2]', 'text-[#5C6454]');
            btn.classList.add('bg-amber-600', 'hover:bg-amber-700', 'text-white', 'shadow-sm', 'animate-pulse');
            btn.title = "Ada perubahan belum disimpan! Klik untuk simpan.";
            icon.innerText = "save";
            icon.classList.remove('text-emerald-700');
            icon.classList.add('text-white');
            label.innerText = "Simpan *";
        }

        updateBulkSaveUI();
    }

    function markRowClean(id) {
        dirtyRows.delete(id);
        const row = document.getElementById('row-' + id);
        if (row) {
            row.classList.remove('bg-amber-50/70', '!border-amber-500');
            row.classList.add('border-transparent', 'bg-emerald-50/80');
            setTimeout(() => {
                row.classList.remove('bg-emerald-50/80');
            }, 1200);
        }

        const btn = document.getElementById('btn-save-row-' + id);
        const icon = document.getElementById('icon-save-' + id);
        const label = document.getElementById('label-save-' + id);

        if (btn && icon && label) {
            btn.classList.add('bg-[#EAE1D4]', 'hover:bg-[#DCD1C2]', 'text-[#5C6454]');
            btn.classList.remove('bg-amber-600', 'hover:bg-amber-700', 'text-white', 'shadow-sm', 'animate-pulse');
            btn.title = "Status: Tersimpan (Klik untuk simpan ulang)";
            icon.innerText = "check_circle";
            icon.classList.add('text-emerald-700');
            icon.classList.remove('text-white');
            label.innerText = "Tersimpan";
        }

        updateBulkSaveUI();
    }

    function updateBulkSaveUI() {
        const count = dirtyRows.size;
        const btnTop = document.getElementById('btn-save-all-changes');
        const textTop = document.getElementById('btn-save-all-text');
        const floatBar = document.getElementById('floating-unsaved-bar');
        const floatCount = document.getElementById('floating-unsaved-count');

        if (textTop) {
            textTop.innerText = 'Simpan Semua (' + count + ')';
        }

        if (count > 0) {
            if (btnTop) {
                btnTop.removeAttribute('disabled');
                btnTop.classList.remove('opacity-50', 'pointer-events-none');
                btnTop.classList.add('animate-pulse');
            }
            if (floatBar) {
                floatBar.classList.remove('translate-y-32', 'opacity-0', 'pointer-events-none');
                floatBar.classList.add('translate-y-0', 'opacity-100');
                if (floatCount) floatCount.innerText = 'Ada ' + count + ' produk dengan perubahan belum disimpan!';
            }
        } else {
            if (btnTop) {
                btnTop.setAttribute('disabled', 'true');
                btnTop.classList.add('opacity-50', 'pointer-events-none');
                btnTop.classList.remove('animate-pulse');
            }
            if (floatBar) {
                floatBar.classList.add('translate-y-32', 'opacity-0', 'pointer-events-none');
                floatBar.classList.remove('translate-y-0', 'opacity-100');
            }
        }
    }

    async function saveSingleRow(id) {
        const btn = document.getElementById('btn-save-row-' + id);
        const label = document.getElementById('label-save-' + id);
        const prevText = label.innerText;
        label.innerText = 'Menyimpan...';

        const payload = {
            id: id,
            name: document.getElementById('name-' + id)?.value,
            sub_category: document.getElementById('subcat-' + id)?.value,
            cost_price: parseFloat(document.getElementById('cost-' + id)?.value) || 0,
            selling_price: parseFloat(document.getElementById('sell-' + id)?.value) || 0,
            badge: document.getElementById('badge-' + id)?.value,
            status: document.getElementById('status-' + id)?.value || 'active',
            _token: '{{ csrf_token() }}'
        };

        try {
            const url = '{{ route("admin.products.update", ":id") }}'.replace(':id', id);
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data.status === 'success') {
                markRowClean(id);
            } else {
                alert('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan'));
                label.innerText = prevText;
            }
        } catch (e) {
            // Fallback submit form if fetch fails
            const form = document.getElementById('form-update-' + id);
            if (form) form.submit();
        }
    }

    async function saveAllChangedProducts() {
        if (dirtyRows.size === 0) return;

        const btnTop = document.getElementById('btn-save-all-changes');
        const btnFloat = document.getElementById('btn-floating-save');
        if (btnTop) btnTop.innerText = 'Menyimpan Semua...';
        if (btnFloat) btnFloat.innerText = 'Menyimpan...';

        const productsToSave = [];
        dirtyRows.forEach(id => {
            productsToSave.push({
                id: id,
                name: document.getElementById('name-' + id)?.value,
                sub_category: document.getElementById('subcat-' + id)?.value,
                cost_price: parseFloat(document.getElementById('cost-' + id)?.value) || 0,
                selling_price: parseFloat(document.getElementById('sell-' + id)?.value) || 0,
                badge: document.getElementById('badge-' + id)?.value,
                status: document.getElementById('status-' + id)?.value || 'active'
            });
        });

        try {
            const res = await fetch('{{ route("admin.products.batch-update") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    products: productsToSave
                })
            });

            const data = await res.json();
            if (data.status === 'success') {
                const savedIds = Array.from(dirtyRows);
                savedIds.forEach(id => markRowClean(id));
                alert('✓ ' + data.message);
            } else {
                alert('Gagal menyimpan massal: ' + (data.message || 'Terjadi kesalahan'));
            }
        } catch (e) {
            alert('Terjadi kesalahan jaringan saat menyimpan massal.');
        } finally {
            updateBulkSaveUI();
        }
    }

    function recalculateRow(id) {
        const cost = parseFloat(document.getElementById('cost-' + id).value) || 0;
        const sell = parseFloat(document.getElementById('sell-' + id).value) || 0;
        
        const profit = sell - cost;
        let percent = 0;
        if (cost > 0) {
            percent = ((profit / cost) * 100).toFixed(1);
        } else {
            percent = 100;
        }

        document.getElementById('profit-display-' + id).innerText = '+ Rp ' + Math.round(profit).toLocaleString('id-ID');
        document.getElementById('margin-display-' + id).innerText = (cost > 0 ? percent + '% margin' : 'Biaya Admin (Laba)');
    }

    function updateChangeProfit(changeId, sellPrice, costPrice) {
        const sell = parseFloat(sellPrice) || 0;
        const profit = sell - costPrice;
        const el = document.getElementById('change-profit-' + changeId);
        if (el) {
            el.innerText = 'Rp ' + Math.round(profit).toLocaleString('id-ID');
            el.className = profit >= 0 ? 'text-emerald-700 font-extrabold' : 'text-rose-700 font-extrabold';
        }
    }

    function openBulkMarginModal() {
        document.getElementById('bulk-margin-modal').classList.remove('hidden');
    }

    function closeBulkMarginModal() {
        document.getElementById('bulk-margin-modal').classList.add('hidden');
    }
</script>
@endpush
@endsection
