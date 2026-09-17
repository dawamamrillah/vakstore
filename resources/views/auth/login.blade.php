@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-12">
    <div class="max-w-md mx-auto flex flex-col gap-6">
        
        <div class="bg-[#FFFFFF] border border-[#525A43]/20 rounded-3xl p-8 shadow-md flex flex-col gap-6">
            <div class="text-center flex flex-col items-center">
                <img src="{{ asset('images/logo.png') }}" alt="VAKSTORE Logo" class="h-16 w-auto object-contain mb-2">
                <h1 class="text-2xl font-extrabold text-[#1F2419]">Masuk ke VAKSTORE</h1>
                <p class="text-xs text-[#596152] mt-1">Akses Saldo Vault privat dan riwayat pesanan Anda</p>
            </div>

            <form action="{{ route('login.post') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-[#2c3325]">Alamat Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email', 'rian@vakstore.id') }}" 
                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                        placeholder="nama@email.com" 
                        required
                    >
                </div>

                <div class="flex flex-col gap-1">
                    <div class="flex justify-between items-center">
                        <label class="text-xs font-bold text-[#2c3325]">Kata Sandi</label>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        value="password"
                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                        placeholder="••••••••" 
                        required
                    >
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-[#596152]">
                        <input type="checkbox" name="remember" class="rounded border-[#DCD1C2] text-[#525A43] focus:ring-[#525A43]">
                        <span>Ingat Saya</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 rounded-xl bg-[#525A43] hover:bg-[#3B432D] text-white font-extrabold text-xs transition-all shadow-sm flex items-center justify-center gap-1.5 mt-2"
                >
                    <span class="material-symbols-outlined text-base">login</span>
                    <span>Masuk Sekarang</span>
                </button>
            </form>

            <!-- Quick Demo Accounts -->
            <div class="pt-4 border-t border-[#DCD1C2] flex flex-col gap-2">
                <span class="text-[10px] text-center uppercase font-bold text-[#76786f]">Akun Siap Uji (1-Klik Login):</span>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('demo-login', 'user') }}" class="p-2 rounded-xl bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white border border-[#525A43]/20 text-[11px] font-bold text-center transition-colors">
                        👤 Member VIP
                    </a>
                    <a href="{{ route('demo-login', 'admin') }}" class="p-2 rounded-xl bg-amber-50 hover:bg-amber-800 hover:text-white border border-amber-300 text-[11px] font-bold text-amber-900 text-center transition-colors">
                        🛡️ Super Admin
                    </a>
                </div>
            </div>

            <p class="text-center text-xs text-[#596152]">
                Belum punya akun? <a href="{{ route('register') }}" class="font-bold text-[#525A43] underline">Daftar Akun Baru</a>
            </p>
        </div>

    </div>
</div>
@endsection
