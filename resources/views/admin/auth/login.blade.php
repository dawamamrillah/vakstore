<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Masuk Administrator — VAKSTORE Control Center</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts & Material Symbols -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#525A43",
                        "primary-dark": "#3B432D",
                        "warm-bg": "#E7DBCD",
                        "surface-base": "#F4EFE6",
                        "charcoal": "#1F2419",
                    },
                    fontFamily: {
                        sans: ["Plus Jakarta Sans", "sans-serif"],
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
            background-image: radial-gradient(#525A43 0.75px, transparent 0.75px), radial-gradient(#525A43 0.75px, #E7DBCD 0.75px);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
            background-opacity: 0.05;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 selection:bg-[#525A43] selection:text-white antialiased">

    <div class="w-full max-w-md flex flex-col gap-6 my-8">
        
        <!-- Header Brand Badge -->
        <div class="text-center flex flex-col items-center">
            <div class="inline-flex items-center gap-2 bg-[#FFFFFF]/90 backdrop-blur-md border border-[#525A43]/20 px-4 py-2 rounded-2xl shadow-sm mb-4">
                <img src="{{ asset('images/logo.png') }}" alt="VAKSTORE Logo" class="h-8 w-auto object-contain">
                <span class="font-extrabold text-lg tracking-tight text-[#1F2419]">VAK<span class="text-[#525A43]">CONTROL</span></span>
                <span class="text-[10px] bg-amber-100 text-amber-900 font-bold px-2 py-0.5 rounded-full border border-amber-300">ADMIN ONLY</span>
            </div>
            <h1 class="text-2xl font-extrabold text-[#1F2419] tracking-tight">Portal Akses Administrator</h1>
            <p class="text-xs text-[#596152] mt-1 max-w-xs">Masuk untuk mengelola produk, transaksi, margin laba, dan telemetri Digiflazz.</p>
        </div>

        <!-- Main Login Card -->
        <div class="bg-[#FFFFFF] border border-[#525A43]/20 rounded-3xl p-8 shadow-[0_12px_32px_rgba(82,90,67,0.12)] flex flex-col gap-5">
            
            <!-- Security Badge -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-[#F4EFE6] border border-[#525A43]/15 text-xs text-[#3B432D]">
                <div class="flex items-center gap-2 font-medium">
                    <span class="material-symbols-outlined text-emerald-700 text-base">lock</span>
                    <span>256-Bit Encrypted Admin Gateway</span>
                </div>
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" title="Gateway Siap"></span>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-300 text-emerald-900 px-4 py-3 rounded-xl text-xs flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600 text-base">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-300 text-red-900 px-4 py-3 rounded-xl text-xs flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-600 text-base">error</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-300 text-red-900 px-4 py-3 rounded-xl text-xs flex flex-col gap-1">
                    <div class="flex items-center gap-1.5 font-bold">
                        <span class="material-symbols-outlined text-red-600 text-base">error</span>
                        <span>Autentikasi Gagal:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 pl-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.login.post') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                
                <!-- Email Field -->
                <div class="flex flex-col gap-1.5">
                    <label for="admin_email" class="text-xs font-bold text-[#1F2419] flex items-center justify-between">
                        <span>Alamat Email Administrator</span>
                        <span class="text-[10px] text-[#596152] font-normal">Super Admin Account</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[#76786f] text-lg pointer-events-none">shield_person</span>
                        <input 
                            id="admin_email"
                            type="email" 
                            name="email" 
                            value="{{ old('email', 'admin@vakstore.id') }}" 
                            class="w-full h-11 bg-[#F4EFE6]/70 border border-[#DCD1C2] pl-10 pr-3.5 rounded-xl text-xs font-bold text-[#1F2419] focus:outline-none focus:border-[#525A43] focus:bg-white transition-all shadow-inner" 
                            placeholder="admin@vakstore.id" 
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password Field -->
                <div class="flex flex-col gap-1.5">
                    <label for="admin_password" class="text-xs font-bold text-[#1F2419]">
                        <span>Kata Sandi Master</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[#76786f] text-lg pointer-events-none">key</span>
                        <input 
                            id="admin_password"
                            type="password" 
                            name="password" 
                            value="password"
                            class="w-full h-11 bg-[#F4EFE6]/70 border border-[#DCD1C2] pl-10 pr-10 rounded-xl text-xs font-bold text-[#1F2419] focus:outline-none focus:border-[#525A43] focus:bg-white transition-all shadow-inner tracking-wider" 
                            placeholder="••••••••••••" 
                            required
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility()" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-[#76786f] hover:text-[#1F2419] focus:outline-none p-1"
                            title="Tampilkan / Sembunyikan"
                        >
                            <span id="pw_icon" class="material-symbols-outlined text-lg">visibility</span>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-[#596152] select-none">
                        <input type="checkbox" name="remember" value="1" checked class="rounded border-[#DCD1C2] text-[#525A43] focus:ring-[#525A43]">
                        <span class="font-medium">Ingat Sesi di Perangkat Ini</span>
                    </label>
                    <span class="text-[10px] text-amber-800 font-bold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">IP Logged</span>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3.5 rounded-xl bg-[#3B432D] hover:bg-[#2A3020] active:scale-[0.99] text-white font-extrabold text-xs tracking-wider uppercase transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 mt-2"
                >
                    <span class="material-symbols-outlined text-base">admin_panel_settings</span>
                    <span>Masuk ke Control Center</span>
                </button>
            </form>

            <!-- Quick Auto-Fill / Testing Helper -->
            <div class="pt-3 border-t border-[#DCD1C2] flex flex-col gap-2">
                <button 
                    type="button" 
                    onclick="fillAdminCredentials()" 
                    class="w-full py-2 px-3 rounded-xl bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white border border-[#525A43]/20 text-[11px] font-bold text-[#3B432D] transition-colors flex items-center justify-center gap-1.5"
                >
                    <span class="material-symbols-outlined text-sm">auto_fix_high</span>
                    <span>Isi Kredensial Default (admin@vakstore.id)</span>
                </button>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="flex flex-col items-center gap-3 text-center text-xs text-[#596152]">
            <p class="text-[11px] max-w-xs leading-relaxed">
                🛡️ Sistem ini diproteksi secara khusus. Akses tanpa izin merupakan pelanggaran privasi dan keamanan sistem.
            </p>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#3B432D] hover:text-[#1F2419] hover:underline transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                <span>Kembali ke Halaman Toko</span>
            </a>
        </div>

    </div>

    <script>
        function togglePasswordVisibility() {
            const pwInput = document.getElementById('admin_password');
            const pwIcon = document.getElementById('pw_icon');
            if (pwInput.type === 'password') {
                pwInput.type = 'text';
                pwIcon.textContent = 'visibility_off';
            } else {
                pwInput.type = 'password';
                pwIcon.textContent = 'visibility';
            }
        }

        function fillAdminCredentials() {
            document.getElementById('admin_email').value = 'admin@vakstore.id';
            document.getElementById('admin_password').value = 'password';
        }
    </script>
</body>
</html>
