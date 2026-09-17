@extends('layouts.app')

@section('content')
<div class="max-w-content mx-auto px-4 md:px-8 lg:px-12 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- LEFT: DEPOSIT FORM (4 Cols) -->
        <div class="lg:col-span-4 flex flex-col gap-6">
            <div class="bg-[#FFFFFF] border border-[#525A43]/20 rounded-3xl p-6 shadow-md flex flex-col gap-4">
                <span class="text-[10px] text-[#596152] uppercase font-bold">Saldo Vault Anda</span>
                <h2 class="text-3xl font-extrabold text-[#525A43]">Rp {{ number_format($user->balance, 0, ',', '.') }}</h2>
                <p class="text-xs text-[#596152] leading-relaxed">
                    Saldo Vault dapat digunakan langsung untuk membeli seluruh produk top up game & PPOB secara instan tanpa biaya admin.
                </p>
                <hr class="border-[#DCD1C2]">

                <h3 class="text-sm font-extrabold text-[#1F2419]">Isi Ulang Saldo Vault</h3>
                <form action="{{ route('user.wallet.deposit') }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-[#2c3325]">Nominal Deposit (Rp)</label>
                        <input 
                            type="number" 
                            name="amount" 
                            id="deposit-amount-input" 
                            value="100000" 
                            min="10000" 
                            max="10000000"
                            class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3.5 rounded-xl text-sm font-extrabold text-[#1F2419] focus:outline-none focus:border-[#525A43]" 
                            required
                        >
                    </div>

                    <!-- Preset Nominal Chips -->
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="document.getElementById('deposit-amount-input').value = 50000" class="py-1.5 bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white rounded-lg text-xs font-bold border border-[#525A43]/15 transition-colors">50k</button>
                        <button type="button" onclick="document.getElementById('deposit-amount-input').value = 100000" class="py-1.5 bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white rounded-lg text-xs font-bold border border-[#525A43]/15 transition-colors">100k</button>
                        <button type="button" onclick="document.getElementById('deposit-amount-input').value = 250000" class="py-1.5 bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white rounded-lg text-xs font-bold border border-[#525A43]/15 transition-colors">250k</button>
                        <button type="button" onclick="document.getElementById('deposit-amount-input').value = 500000" class="py-1.5 bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white rounded-lg text-xs font-bold border border-[#525A43]/15 transition-colors">500k</button>
                        <button type="button" onclick="document.getElementById('deposit-amount-input').value = 1000000" class="py-1.5 bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white rounded-lg text-xs font-bold border border-[#525A43]/15 transition-colors">1 Juta</button>
                        <button type="button" onclick="document.getElementById('deposit-amount-input').value = 2000000" class="py-1.5 bg-[#F4EFE6] hover:bg-[#525A43] hover:text-white rounded-lg text-xs font-bold border border-[#525A43]/15 transition-colors">2 Juta</button>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-[#2c3325]">Metode Deposit</label>
                        <select name="payment_method" class="w-full h-11 bg-[#fbf8f4] border border-[#DCD1C2] px-3 rounded-xl text-xs font-semibold text-[#1F2419]">
                            <option value="qris">QRIS Instant (BCA, GoPay, OVO, DANA)</option>
                            <option value="bca_va">BCA Virtual Account</option>
                            <option value="bri_va">BRI Virtual Account</option>
                            <option value="mandiri_va">Mandiri Virtual Account</option>
                        </select>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full py-3 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-extrabold shadow-sm transition-all flex items-center justify-center gap-1.5"
                    >
                        <span class="material-symbols-outlined text-base">add_card</span>
                        <span>Konfirmasi Deposit Instan</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT: MUTATION HISTORY (8 Cols) -->
        <div class="lg:col-span-8 flex flex-col gap-4">
            <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm flex flex-col gap-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-[#DCD1C2]">
                    <div>
                        <h3 class="text-lg font-extrabold text-[#1F2419]">Mutasi & Riwayat Saldo Vault</h3>
                        <p class="text-xs text-[#596152]">Audit pencatatan saldo sebelum dan sesudah transaksi</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <a href="{{ route('user.wallet') }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ !request()->filled('type') ? 'bg-[#525A43] text-white' : 'bg-[#F4EFE6] text-[#596152]' }}">Semua</a>
                        <a href="{{ route('user.wallet', ['type' => 'deposit']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ request('type') === 'deposit' ? 'bg-[#525A43] text-white' : 'bg-[#F4EFE6] text-[#596152]' }}">Masuk</a>
                        <a href="{{ route('user.wallet', ['type' => 'purchase']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ request('type') === 'purchase' ? 'bg-[#525A43] text-white' : 'bg-[#F4EFE6] text-[#596152]' }}">Keluar</a>
                        <a href="{{ route('user.wallet', ['type' => 'refund']) }}" class="px-3 py-1 rounded-lg text-xs font-bold {{ request('type') === 'refund' ? 'bg-[#525A43] text-white' : 'bg-[#F4EFE6] text-[#596152]' }}">Refund</a>
                    </div>
                </div>

                @if($mutations->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left">
                            <thead>
                                <tr class="text-[10px] text-[#596152] uppercase font-bold border-b border-[#DCD1C2]">
                                    <th class="pb-3">Tanggal</th>
                                    <th class="pb-3">Tipe</th>
                                    <th class="pb-3">Keterangan / Ref</th>
                                    <th class="pb-3">Nominal</th>
                                    <th class="pb-3 text-right">Saldo Akhir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#DCD1C2]/60">
                                @foreach($mutations as $m)
                                    <tr>
                                        <td class="py-3 text-[#596152] whitespace-nowrap">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="py-3">
                                            @if($m->type === 'deposit')
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-100 text-emerald-800">DEPOSIT</span>
                                            @elseif($m->type === 'purchase')
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-slate-100 text-slate-800">BELANJA</span>
                                            @elseif($m->type === 'refund')
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-sky-100 text-sky-800">REFUND</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-100 text-amber-800">ADJUSTMENT</span>
                                            @endif
                                        </td>
                                        <td class="py-3">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-[#1F2419]">{{ $m->description }}</span>
                                                <span class="text-[10px] text-[#76786f] font-mono">{{ $m->reference }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3 font-extrabold {{ $m->type === 'purchase' ? 'text-red-700' : 'text-emerald-700' }} whitespace-nowrap">
                                            {{ $m->type === 'purchase' ? '-' : '+' }} Rp {{ number_format($m->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 text-right font-extrabold text-[#1F2419] whitespace-nowrap">
                                            Rp {{ number_format($m->balance_after, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4">
                        {{ $mutations->links() }}
                    </div>
                @else
                    <div class="text-center py-12 text-xs text-[#596152]">
                        <span class="material-symbols-outlined text-4xl mb-1 text-[#76786f]">account_balance_wallet</span>
                        <p>Belum ada mutasi saldo tercatat.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
