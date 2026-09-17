@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-10">
    <div class="flex flex-col mb-8">
        <span class="text-xs font-bold uppercase tracking-widest text-[#525A43]">Portal Resmi</span>
        <h1 class="text-3xl font-extrabold text-[#1F2419] tracking-tight mt-1">Direktori Top Up Game VAKSTORE</h1>
        <p class="text-xs text-[#596152] mt-1">Pilih judul game yang ingin Anda top up dengan jaminan harga kompetitif dan transmisi kilat 24 Jam.</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5">
        @foreach($games as $game)
            <a href="{{ route('topup.show', $game->slug) }}" class="group bg-[#FFFFFF] border border-[#525A43]/15 rounded-3xl p-5 shadow-sm hover:shadow-md hover:border-[#525A43] transition-all flex flex-col items-center text-center">
                <div class="w-24 h-24 rounded-2xl overflow-hidden mb-3 bg-[#F4EFE6] border border-[#525A43]/15 shadow-xs group-hover:scale-105 transition-transform duration-300 flex items-center justify-center">
                    @if($game->image)
                        <img src="{{ $game->image }}" alt="{{ $game->name }}" class="w-full h-full object-cover">
                    @else
                        <span class="material-symbols-outlined text-4xl text-[#525A43]">sports_esports</span>
                    @endif
                </div>
                <span class="text-[10px] uppercase tracking-wider text-[#525A43] font-bold mb-0.5">{{ $game->publisher }}</span>
                <h3 class="text-sm font-bold text-[#1F2419] group-hover:text-[#525A43] transition-colors leading-snug line-clamp-1">{{ $game->name }}</h3>
                <span class="mt-3 text-[11px] text-[#596152] bg-[#F4EFE6] px-3 py-1 rounded-full border border-[#525A43]/10 font-semibold">
                    {{ $game->products->count() }} Pilihan Produk
                </span>
            </a>
        @endforeach
    </div>
</div>
@endsection
