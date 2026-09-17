@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-12">
    <div class="max-w-md mx-auto flex flex-col gap-6">
        
        <div class="bg-[#FFFFFF] border border-[#525A43]/20 rounded-3xl p-8 shadow-md flex flex-col gap-6">
            <div class="text-center flex flex-col items-center">
                <img src="{{ asset('images/logo.png') }}" alt="VAKSTORE Logo" class="h-16 w-auto object-contain mb-2">
                <h1 class="text-2xl font-extrabold text-[#1F2419]">Daftar Member VAKSTORE</h1>
                <p class="text-xs text-[#596152] mt-1">Dapatkan akses instan ke Saldo Vault dan promo eksklusif</p>
            </div>

            <form action="{{ route('register.post') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-[#2c3325]">Nama Lengkap</label>
                    <input 
                        type="text" 
                        name="name" 
                        value="{{ old('name') }}" 
                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                        placeholder="Contoh: Rian Pratama" 
                        required
                    >
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-[#2c3325]">Alamat Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                        placeholder="nama@email.com" 
                        required
                    >
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-[#2c3325]">Nomor WhatsApp / HP</label>
                    <input 
                        type="text" 
                        name="phone" 
                        value="{{ old('phone') }}" 
                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                        placeholder="08xxxxxxxxxx" 
                        required
                    >
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-[#2c3325]">Kata Sandi</label>
                    <input 
                        type="password" 
                        name="password" 
                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                        placeholder="Minimal 6 karakter" 
                        required
                    >
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs font-bold text-[#2c3325]">Konfirmasi Kata Sandi</label>
                    <input 
                        type="password" 
                        name="password_confirmation" 
                        class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                        placeholder="Ulangi kata sandi" 
                        required
                    >
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 rounded-xl bg-[#525A43] hover:bg-[#3B432D] text-white font-extrabold text-xs transition-all shadow-sm flex items-center justify-center gap-1.5 mt-2"
                >
                    <span class="material-symbols-outlined text-base">person_add</span>
                    <span>Buat Akun Member</span>
                </button>
            </form>

            <p class="text-center text-xs text-[#596152]">
                Sudah memiliki akun? <a href="{{ route('login') }}" class="font-bold text-[#525A43] underline">Masuk di Sini</a>
            </p>
        </div>

    </div>
</div>
@endsection
