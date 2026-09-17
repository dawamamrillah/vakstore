<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>{{ $title ?? 'VAKSTORE — Super Admin Telemetry & Control Panel' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3B432D",
                        "primary-container": "#525A43",
                        "warm-bg": "#E7DBCD",
                        "warm-card": "#FFFFFF",
                        "warm-card-subtle": "#F6F0E8",
                        "warm-border": "#DCD1C2",
                        "charcoal": "#1A2016",
                        "muted-olive": "#5C6454",
                        "success-green": "#2D6A36",
                        "warning-gold": "#966614",
                        "danger-red": "#B02A2A",
                    },
                    fontFamily: {
                        sans: ["Plus Jakarta Sans", "sans-serif"],
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
            color: #1A2016;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#E7DBCD] text-[#1A2016] antialiased min-h-screen flex flex-col">

    <!-- Admin Header Navigation -->
    <header class="fixed top-0 left-0 right-0 w-full z-50 bg-[#F5ECE2]/95 backdrop-blur-xl border-b border-[#DCD1C2] shadow-[0_2px_16px_rgba(40,30,20,0.06)]">
        <div class="h-20 max-w-content mx-auto px-4 md:px-8 lg:px-12 flex items-center justify-between gap-4">
            
            <!-- Brand Logo -->
            <div class="flex items-center gap-6">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('images/logo.png') }}" alt="VAKSTORE Logo" class="h-10 w-auto object-contain shrink-0">
                    <div class="flex flex-col">
                        <span class="font-bold text-xl tracking-tight text-[#1A2016]">VAK<span class="text-[#525A43]">CONTROL</span></span>
                        <span class="text-[10px] text-[#5C6454] tracking-widest uppercase font-semibold">Super Admin Core</span>
                    </div>
                </a>

                <div class="hidden lg:flex items-center gap-2 bg-[#FFFFFF] border border-[#DCD1C2] px-3 py-1 rounded-full text-xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="font-medium text-[#1A2016]">Mock Provider Core: <span class="text-emerald-700 font-bold">ONLINE</span></span>
                </div>
            </div>

            <!-- Admin Navigation Tabs -->
            <nav class="hidden md:flex items-center gap-1">
                <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#5C6454] hover:text-[#1A2016] hover:bg-[#FFFFFF]/60' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.transactions') }}" class="px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors {{ request()->routeIs('admin.transactions*') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#5C6454] hover:text-[#1A2016] hover:bg-[#FFFFFF]/60' }}">
                    Transaksi
                </a>
                <a href="{{ route('admin.products') }}" class="px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors {{ request()->routeIs('admin.products*') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#5C6454] hover:text-[#1A2016] hover:bg-[#FFFFFF]/60' }}">
                    Produk & Laba
                </a>
                <a href="{{ route('admin.users') }}" class="px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors {{ request()->routeIs('admin.users*') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#5C6454] hover:text-[#1A2016] hover:bg-[#FFFFFF]/60' }}">
                    Kelola User
                </a>
                <a href="{{ route('admin.vouchers') }}" class="px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors {{ request()->routeIs('admin.vouchers*') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#5C6454] hover:text-[#1A2016] hover:bg-[#FFFFFF]/60' }}">
                    Voucher
                </a>
                <a href="{{ route('admin.reports') }}" class="px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors {{ request()->routeIs('admin.reports*') ? 'bg-[#525A43] text-white shadow-sm' : 'text-[#5C6454] hover:text-[#1A2016] hover:bg-[#FFFFFF]/60' }}">
                    Laporan
                </a>
            </nav>

            <!-- Admin Actions & Profile -->
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#FFFFFF] border border-[#DCD1C2] hover:bg-[#F4EFE6] text-[#1A2016] flex items-center gap-1 shadow-xs">
                    <span class="material-symbols-outlined text-sm">storefront</span>
                    Lihat Website
                </a>

                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 rounded-xl bg-red-100 border border-red-200 text-red-700 hover:bg-red-200 transition-colors flex items-center justify-center" title="Keluar dari Admin Control Center">
                        <span class="material-symbols-outlined text-base">logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
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
        </div>

        @yield('content')
    </main>

    <footer class="w-full bg-[#E7DBCD] border-t border-[#DCD1C2] py-6 mt-12">
        <div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-[#5C6454]">
            <p>VAKSTORE Super Admin Telemetry • Core Engine v4.2</p>
            <p>Sesi Aktif: {{ auth()->user()->name }} ({{ auth()->user()->email }})</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
