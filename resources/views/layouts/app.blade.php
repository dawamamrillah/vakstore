<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>{{ $title ?? 'VAKSTORE — Exclusive Game Top Up & PPOB Portal' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Tailwind CSS CDN with Form & Container Plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#525A43",
                        "primary-dark": "#3B432D",
                        "primary-container": "#525A43",
                        "secondary": "#596152",
                        "warm-bg": "#E7DBCD",
                        "surface": "#E7DBCD",
                        "surface-base": "#F4EFE6",
                        "surface-raised": "#FFFFFF",
                        "surface-container": "#EFE7DC",
                        "border-subtle": "rgba(82, 90, 67, 0.12)",
                        "border-card": "#DCD1C2",
                        "charcoal": "#1F2419",
                        "status-success": "#397341",
                        "status-pending": "#9e6718",
                        "status-failed": "#b3392f",
                    },
                    fontFamily: {
                        sans: ["Plus Jakarta Sans", "sans-serif"],
                        display: ["Plus Jakarta Sans", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        "2xl": "1rem",
                        full: "9999px"
                    },
                    maxWidth: {
                        content: "1280px"
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #E7DBCD;
            color: #1F2419;
        }
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-thumb {
            background: #525A43;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-track {
            background: #E7DBCD;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#E7DBCD] text-[#1F2419] antialiased selection:bg-[#525A43] selection:text-white min-h-screen flex flex-col">

    <!-- Luxury Header Navigation -->
    <header class="fixed top-0 left-0 right-0 w-full z-50 bg-[#E7DBCD]/95 backdrop-blur-xl border-b border-[#525A43]/15 shadow-[0_2px_16px_rgba(82,90,67,0.08)]">
        <div class="h-20 max-w-content mx-auto px-4 md:px-8 lg:px-12 flex items-center justify-between gap-4">
            
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-3 group shrink-0 select-none py-1">
                <img src="{{ asset('images/logo.png') }}" alt="VAKSTORE Logo" class="h-10 md:h-11 w-auto object-contain rounded-md shrink-0">
                <div class="flex flex-col shrink-0">
                    <span class="font-extrabold text-xl md:text-2xl tracking-tight text-[#1F2419] whitespace-nowrap">VAK<span class="text-[#525A43]">STORE</span></span>
                    <span class="text-[10px] text-[#525A43] tracking-widest uppercase font-bold whitespace-nowrap">Since 2026</span>
                </div>
            </a>

            <!-- Nav Links -->
            <nav class="hidden lg:flex items-center gap-1">
                <a href="{{ route('home') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('home') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#46483f] hover:text-[#1F2419] hover:bg-[#F4EFE6]' }}">
                    Beranda
                </a>
                <a href="{{ route('topup.index') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ (request()->routeIs('topup.*') && !request()->is('topup/telkomsel*') && !request()->is('topup/indosat*') && !request()->is('topup/xl*') && !request()->is('topup/axis*') && !request()->is('topup/tri*') && !request()->is('topup/smartfren*') && !request()->is('topup/byu*')) ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#46483f] hover:text-[#1F2419] hover:bg-[#F4EFE6]' }}">
                    Topup Games
                </a>
                <a href="{{ route('ppob.index') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ (request()->is('layanan-ppob') || request()->is('topup/telkomsel*') || request()->is('topup/indosat*') || request()->is('topup/xl*') || request()->is('topup/axis*') || request()->is('topup/tri*') || request()->is('topup/smartfren*') || request()->is('topup/byu*') || (request()->routeIs('ppob.*') && !request()->is('ppob/pln*') && !request()->is('ppob/pdam*') && !request()->is('ppob/telkom*'))) ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#46483f] hover:text-[#1F2419] hover:bg-[#F4EFE6]' }}">
                    Pulsa &amp; Data
                </a>
                
                <!-- Tagihan Dropdown (PDAM & Internet) -->
                <div class="relative group/tagihan" id="nav-tagihan-dropdown">
                    <button 
                        type="button" 
                        class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-1 cursor-pointer {{ (request()->is('ppob/pdam*') || request()->is('ppob/telkom*')) ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#46483f] hover:text-[#1F2419] hover:bg-[#F4EFE6]' }}"
                        aria-expanded="false"
                    >
                        <span>Tagihan</span>
                        <span class="material-symbols-outlined text-base transition-transform group-hover/tagihan:rotate-180">expand_more</span>
                    </button>
                    <div class="hidden group-hover/tagihan:block absolute top-full left-0 mt-1 w-60 bg-[#FFFFFF] border border-[#525A43]/20 rounded-2xl shadow-xl p-2 z-50">
                        <a href="{{ route('ppob.show', 'pdam-nusantara') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-[#1F2419] hover:bg-[#F4EFE6] hover:text-[#525A43] transition-colors {{ request()->is('ppob/pdam*') ? 'bg-[#F4EFE6] text-[#525A43]' : '' }}">
                            <div class="w-8 h-8 rounded-lg bg-cyan-50 border border-cyan-200 text-cyan-800 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-base">water_drop</span>
                            </div>
                            <div class="flex flex-col">
                                <span>Tagihan PDAM</span>
                                <span class="text-[10px] text-[#76786f] font-normal">50 Wilayah Air Minum</span>
                            </div>
                        </a>
                        <a href="{{ route('ppob.show', 'telkom-indihome') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold text-[#1F2419] hover:bg-[#F4EFE6] hover:text-[#525A43] transition-colors mt-1 {{ request()->is('ppob/telkom*') ? 'bg-[#F4EFE6] text-[#525A43]' : '' }}">
                            <div class="w-8 h-8 rounded-lg bg-red-50 border border-red-200 text-red-800 flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined text-base">router</span>
                            </div>
                            <div class="flex flex-col">
                                <span>Tagihan Internet</span>
                                <span class="text-[10px] text-[#76786f] font-normal">IndiHome, Biznet, dll</span>
                            </div>
                        </a>
                    </div>
                </div>

                <a href="{{ route('ppob.show', 'pln') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ request()->is('ppob/pln*') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#46483f] hover:text-[#1F2419] hover:bg-[#F4EFE6]' }}">
                    PLN
                </a>
                <a href="{{ route('tracking') }}" class="px-3.5 py-2 rounded-xl text-sm font-semibold transition-colors {{ request()->routeIs('tracking') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#46483f] hover:text-[#1F2419] hover:bg-[#F4EFE6]' }}">
                    Cek Transaksi
                </a>
            </nav>

            <!-- QRIS Dinamis Badge -->
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-[#F4EFE6] border border-[#525A43]/20 px-3.5 py-1.5 rounded-xl shadow-xs gap-2 text-xs font-bold text-[#1F2419]">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="material-symbols-outlined text-base text-[#525A43]">qr_code_2</span>
                    <span>QRIS Dinamis 24/7</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Alerts -->
    <main class="w-full pt-20 flex-1">
        <div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 mt-4">
            @if(session('success'))
                <div class="mb-4 bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-3 rounded-xl flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 text-sm">✕</button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-300 text-red-900 px-4 py-3 rounded-xl flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900 text-sm">✕</button>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-50 border border-red-300 text-red-900 px-4 py-3 rounded-xl shadow-xs">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="material-symbols-outlined text-red-600">error</span>
                        <span class="text-sm font-bold">Terjadi kesalahan:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs space-y-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @yield('content')
    </main>

    <!-- Luxury Modern Footer -->
    <footer class="w-full bg-[#1F2419] text-[#EFE7DC] mt-20 pt-16 pb-12 border-t border-[#525A43]/30">
        <div class="max-w-content mx-auto px-4 md:px-8 lg:px-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 pb-12 border-b border-[#525A43]/30">
                <!-- Brand Overview (2 Cols) -->
                <div class="lg:col-span-2 flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/logo.png') }}" alt="VAKSTORE Logo" class="h-12 w-auto object-contain shrink-0 rounded-lg p-1 bg-[#2C3125] border border-[#525A43]/40">
                        <div class="flex flex-col">
                            <span class="font-bold text-2xl tracking-tight text-white">VAK<span class="text-[#9CA28B]">STORE</span></span>
                            <span class="text-xs text-[#9CA28B] tracking-widest uppercase font-semibold">Official Digital Vault • Since 2026</span>
                        </div>
                    </div>
                    <p class="text-sm text-[#DCD1C2] leading-relaxed max-w-md">
                        Platform agregator top up game resmi &amp; layanan pembelian pulsa dan token listrik berkecepatan tinggi terintegrasi langsung dengan gateway server resmi secara otomatis dan real-time 24 jam.
                    </p>
                    <div class="flex items-center gap-3 pt-2">
                        <span class="px-3 py-1 rounded-full bg-[#2C3125] border border-[#525A43] text-xs text-[#9CA28B] font-medium flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            Sistem Gateway Aktif
                        </span>
                        <span class="px-3 py-1 rounded-full bg-[#2C3125] border border-[#525A43] text-xs text-[#9CA28B] font-medium">
                            256-Bit SSL Encrypted
                        </span>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider">Top Up Game Populer</h4>
                    <ul class="text-sm text-[#DCD1C2] space-y-2">
                        <li><a href="{{ route('topup.show', 'mobile-legends') }}" class="hover:text-white transition-colors">Mobile Legends: Bang Bang</a></li>
                        <li><a href="{{ route('topup.show', 'free-fire') }}" class="hover:text-white transition-colors">Free Fire Garena</a></li>
                        <li><a href="{{ route('topup.show', 'pubg-mobile') }}" class="hover:text-white transition-colors">PUBG Mobile UC</a></li>
                        <li><a href="{{ route('topup.show', 'valorant') }}" class="hover:text-white transition-colors">Valorant Points (VP)</a></li>
                    </ul>
                </div>

                <!-- Pulsa & Listrik Links -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider">Tagihan &amp; Listrik PLN</h4>
                    <ul class="text-sm text-[#DCD1C2] space-y-2">
                        <li><a href="{{ route('ppob.show', 'pln') }}" class="hover:text-white transition-colors">Listrik PLN (Token &amp; Tagihan Bulanan)</a></li>
                        <li><a href="{{ route('ppob.show', 'pdam-nusantara') }}" class="hover:text-white transition-colors">Tagihan Air PDAM (50 Daerah)</a></li>
                        <li><a href="{{ route('ppob.show', 'telkom-indihome') }}" class="hover:text-white transition-colors">Internet IndiHome &amp; Biznet</a></li>
                        <li><a href="{{ route('ppob.index') }}" class="hover:text-white transition-colors">Pulsa &amp; Paket Kuota All Operator</a></li>
                    </ul>
                </div>

                <!-- Security & Navigation -->
                <div class="flex flex-col gap-3">
                    <h4 class="text-sm font-bold text-white uppercase tracking-wider">Akses Cepat</h4>
                    <p class="text-xs text-[#DCD1C2]">Pintasan layanan & pemantauan transaksi:</p>
                    <div class="flex flex-col gap-2 pt-1">
                        <a href="{{ route('tracking') }}" class="px-3 py-1.5 rounded-lg bg-[#2C3125] hover:bg-[#525A43] text-xs font-semibold text-[#DFD2C2] hover:text-white transition-colors flex items-center justify-between border border-[#525A43]">
                            <span>🔍 Lacak Pesanan / Invoice</span>
                            <span class="text-[10px] text-sky-400 font-bold">Publik</span>
                        </a>
                        <a href="{{ route('ppob.index') }}" class="px-3 py-1.5 rounded-lg bg-[#2C3125] hover:bg-[#525A43] text-xs font-semibold text-[#DFD2C2] hover:text-white transition-colors flex items-center justify-between border border-[#525A43]">
                            <span>⚡ Beli Pulsa &amp; Listrik Cepat</span>
                            <span class="text-[10px] text-emerald-400 font-bold">24 Jam</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#9CA28B]">
                <p>© 2026 VAKSTORE Digital Vault. Seluruh Hak Cipta Dilindungi Undang-Undang.</p>
                <div class="flex items-center gap-6">
                    <a href="#" class="hover:text-white transition-colors">Syarat & Ketentuan</a>
                    <a href="#" class="hover:text-white transition-colors">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-white transition-colors">Integritas Transaksi</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
