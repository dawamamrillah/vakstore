@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-12">
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1A2016]">Manajemen Voucher & Promo</h1>
            <p class="text-xs text-[#5C6454]">Buat kode voucher diskon persentase atau nominal dengan batas pemakaian</p>
        </div>
    </div>

    <!-- Create Voucher Form -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm">
        <h3 class="text-base font-extrabold text-[#1A2016] mb-4">Buat Voucher Promo Baru</h3>
        <form action="{{ route('admin.vouchers.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
            @csrf
            
            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-[#5C6454]">Kode Kupon</label>
                <input type="text" name="code" placeholder="CONTOH: RAMADAN50" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 uppercase font-bold rounded-xl text-xs" required>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-[#5C6454]">Nama Promo</label>
                <input type="text" name="name" placeholder="Diskon Spesial Liburan" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 font-semibold rounded-xl text-xs" required>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-[#5C6454]">Tipe Diskon</label>
                <select name="type" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 font-semibold rounded-xl text-xs">
                    <option value="percentage">Persentase (%)</option>
                    <option value="fixed">Nominal Tetap (Rp)</option>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-[#5C6454]">Nilai Diskon (%, atau Rp)</label>
                <input type="number" name="value" placeholder="25 atau 10000" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 font-bold rounded-xl text-xs" required>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-[#5C6454]">Min. Transaksi (Rp)</label>
                <input type="number" name="minimum_transaction" value="20000" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 font-semibold rounded-xl text-xs" required>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-[#5C6454]">Maks. Diskon (Rp)</label>
                <input type="number" name="maximum_discount" value="10000" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 font-semibold rounded-xl text-xs">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-[#5C6454]">Batas Kuota Total</label>
                <input type="number" name="usage_limit" value="100" class="h-10 bg-[#F6F0E8] border border-[#DCD1C2] px-3 font-semibold rounded-xl text-xs" required>
            </div>

            <input type="hidden" name="usage_per_user" value="1">
            <input type="hidden" name="status" value="active">

            <div>
                <button type="submit" class="w-full h-10 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl font-bold text-xs transition-colors shadow-xs">
                    + Terbitkan Voucher
                </button>
            </div>
        </form>
    </div>

    <!-- Voucher List -->
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm">
        <h3 class="text-base font-extrabold text-[#1A2016] mb-4">Daftar Voucher Aktif</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="text-[10px] text-[#5C6454] uppercase font-bold border-b border-[#DCD1C2]">
                        <th class="pb-3">Kode Kupon</th>
                        <th class="pb-3">Nama Promo</th>
                        <th class="pb-3">Besaran</th>
                        <th class="pb-3">Min. Belanja</th>
                        <th class="pb-3">Maks. Potongan</th>
                        <th class="pb-3">Terpakai / Kuota</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#DCD1C2]/60">
                    @foreach($vouchers as $v)
                        <tr>
                            <td class="py-3 font-mono font-extrabold text-[#1A2016] tracking-wider">{{ $v->code }}</td>
                            <td class="py-3 font-bold text-[#1A2016]">{{ $v->name }}</td>
                            <td class="py-3 font-extrabold text-emerald-800">
                                {{ $v->type === 'percentage' ? (int)$v->value . '%' : 'Rp ' . number_format($v->value, 0, ',', '.') }}
                            </td>
                            <td class="py-3 text-[#5C6454]">Rp {{ number_format($v->minimum_transaction, 0, ',', '.') }}</td>
                            <td class="py-3 text-[#5C6454]">
                                {{ $v->maximum_discount ? 'Rp ' . number_format($v->maximum_discount, 0, ',', '.') : 'Tanpa Batas' }}
                            </td>
                            <td class="py-3 font-bold text-[#1A2016]">
                                {{ $v->used_count }} / {{ $v->usage_limit > 0 ? $v->usage_limit : '∞' }}
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $v->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ strtoupper($v->status) }}
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                <form action="{{ route('admin.vouchers.destroy', $v->id) }}" method="POST" onsubmit="return confirm('Hapus voucher ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 bg-red-50 hover:bg-red-600 hover:text-white text-red-700 rounded-lg text-xs font-bold transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
