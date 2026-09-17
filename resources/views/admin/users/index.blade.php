@extends('layouts.admin')

@section('content')
<div class="space-y-6 pb-12">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#1A2016]">Manajemen Pengguna &amp; Akun Admin</h1>
            <p class="text-xs text-[#5C6454]">Tambah pengguna/admin baru, ganti password, hapus pengguna, penyesuaian saldo vault, dan audit trail</p>
        </div>

        <div class="flex items-center gap-3">
            <form action="{{ route('admin.users') }}" method="GET" class="flex items-center gap-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari nama/email/telepon..." 
                    class="h-10 bg-[#FFFFFF] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1A2016] placeholder:text-[#878c7f] focus:outline-none focus:border-[#525A43]"
                >
            </form>

            <button 
                type="button" 
                onclick="openAddUserModal()" 
                class="h-10 px-4 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-xl text-xs font-extrabold flex items-center gap-1.5 transition-all shadow-sm cursor-pointer whitespace-nowrap"
            >
                <span class="material-symbols-outlined text-base">person_add</span>
                <span>Tambah Pengguna</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-emerald-600">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-base text-rose-600">error</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base text-amber-700">warning</span>
                <span>Terdapat kesalahan pengisian data:</span>
            </div>
            <ul class="list-disc list-inside pl-1 text-[11px]">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 lg:p-8 shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead>
                    <tr class="text-[10px] text-[#5C6454] uppercase font-bold border-b border-[#DCD1C2]">
                        <th class="pb-3">Pengguna</th>
                        <th class="pb-3">Email &amp; Kontak</th>
                        <th class="pb-3">Role</th>
                        <th class="pb-3">Saldo Vault</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3">Sesuaikan Saldo Manual</th>
                        <th class="pb-3 text-right">Aksi Akun</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#DCD1C2]/60">
                    @forelse($users as $u)
                        <tr class="hover:bg-[#FDFBF7]/60 transition-colors">
                            <td class="py-3.5 pr-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-[#525A43] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ substr($u->name, 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-extrabold text-[#1A2016]">{{ $u->name }}</span>
                                        <span class="text-[10px] text-[#878c7f]">ID: #{{ $u->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 text-[#5C6454]">
                                <span class="font-semibold text-[#1A2016]">{{ $u->email }}</span><br>
                                <span class="text-[11px] text-[#76786f]">{{ $u->phone ?: 'Tidak ada No. HP' }}</span>
                            </td>
                            <td class="py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $u->isAdmin() ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                                    {{ strtoupper($u->role) }}
                                </span>
                            </td>
                            <td class="py-3.5 font-extrabold text-[#525A43] text-sm whitespace-nowrap">
                                Rp {{ number_format($u->balance, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $u->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ strtoupper($u->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 pr-3">
                                <form action="{{ route('admin.users.adjust-balance', $u->id) }}" method="POST" class="inline-flex items-center gap-1.5">
                                    @csrf
                                    <select name="action_type" class="h-8 bg-[#F6F0E8] border border-[#DCD1C2] px-2 rounded-lg text-[10px] font-bold text-[#1A2016]">
                                        <option value="add">+ Tambah</option>
                                        <option value="subtract">- Kurangi</option>
                                    </select>
                                    <input 
                                        type="number" 
                                        name="amount" 
                                        placeholder="Nominal" 
                                        class="w-24 h-8 bg-[#F6F0E8] border border-[#DCD1C2] px-2 rounded-lg text-xs font-semibold text-[#1A2016]" 
                                        required
                                    >
                                    <input 
                                        type="text" 
                                        name="reason" 
                                        placeholder="Alasan / Audit" 
                                        class="w-28 h-8 bg-[#F6F0E8] border border-[#DCD1C2] px-2 rounded-lg text-xs text-[#1A2016]" 
                                        required
                                    >
                                    <button type="submit" class="px-2.5 py-1.5 bg-[#525A43] hover:bg-[#3B432D] text-white rounded-lg font-bold text-xs transition-colors cursor-pointer">
                                        Eksekusi
                                    </button>
                                </form>
                            </td>
                            <td class="py-3.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    <!-- Ganti Password Button -->
                                    <button 
                                        type="button" 
                                        onclick="openPasswordModal({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ addslashes($u->email) }}')"
                                        class="h-8 px-2.5 bg-[#F4EFE6] hover:bg-[#EAE1D4] border border-[#DCD1C2] text-[#1F2419] rounded-lg text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer"
                                        title="Ganti Password"
                                    >
                                        <span class="material-symbols-outlined text-sm text-[#525A43]">lock_reset</span>
                                        <span>Ganti Pass</span>
                                    </button>

                                    <!-- Hapus Pengguna Button / Form -->
                                    @if(auth()->id() != $u->id)
                                        <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna {{ addslashes($u->name) }} ({{ addslashes($u->email) }})? Tindakan ini tidak dapat dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="h-8 px-2.5 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-lg text-xs font-bold flex items-center gap-1 transition-colors cursor-pointer"
                                                title="Hapus Pengguna"
                                            >
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                                <span>Hapus</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[10px] font-bold text-[#878c7f] px-2 py-1 bg-[#F4EFE6] rounded-lg border border-[#DCD1C2]">Akun Anda</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-[#878c7f]">
                                Tidak ada data pengguna yang sesuai pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-4 border-t border-[#DCD1C2]/60">
            {{ $users->links() }}
        </div>
    </div>

</div>

<!-- MODAL: TAMBAH PENGGUNA / ADMIN BARU -->
<div id="modal-add-user" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl relative animate-in fade-in zoom-in duration-150">
        <div class="flex items-center justify-between pb-4 border-b border-[#DCD1C2]/80">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-[#525A43] text-white flex items-center justify-center shadow-xs">
                    <span class="material-symbols-outlined text-xl">person_add</span>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-[#1A2016]">Tambah Pengguna / Admin Baru</h3>
                    <p class="text-xs text-[#5C6454]">Buat akun pengguna member atau administrator baru</p>
                </div>
            </div>
            <button type="button" onclick="closeAddUserModal()" class="text-[#878c7f] hover:text-[#1A2016] cursor-pointer p-1">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4 pt-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-[#2c3325] mb-1">Nama Lengkap</label>
                <input 
                    type="text" 
                    name="name" 
                    placeholder="Contoh: Budi Santoso / Admin Vakstore" 
                    class="w-full h-11 bg-[#FDFBF7] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]" 
                    required
                >
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#2c3325] mb-1">Email Pengguna</label>
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="contoh@vakstore.id" 
                        class="w-full h-11 bg-[#FDFBF7] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]" 
                        required
                    >
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#2c3325] mb-1">Nomor WhatsApp / HP</label>
                    <input 
                        type="text" 
                        name="phone" 
                        placeholder="081234567890" 
                        class="w-full h-11 bg-[#FDFBF7] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]" 
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#2c3325] mb-1">Role Akun</label>
                    <select name="role" class="w-full h-11 bg-[#FDFBF7] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-bold text-[#1A2016] focus:outline-none focus:border-[#525A43]">
                        <option value="user">User (Member)</option>
                        <option value="admin">Admin (Administrator)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#2c3325] mb-1">Status Akun</label>
                    <select name="status" class="w-full h-11 bg-[#FDFBF7] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-bold text-[#1A2016] focus:outline-none focus:border-[#525A43]">
                        <option value="active">Active (Aktif)</option>
                        <option value="suspended">Suspended (Nonaktif)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#2c3325] mb-1">Password Baru</label>
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Minimal 6 karakter..." 
                    class="w-full h-11 bg-[#FDFBF7] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]" 
                    required 
                    minlength="6"
                >
            </div>

            <div class="pt-3 flex items-center justify-end gap-2.5">
                <button 
                    type="button" 
                    onclick="closeAddUserModal()" 
                    class="px-4 h-11 rounded-xl bg-[#F4EFE6] hover:bg-[#EAE1D4] border border-[#DCD1C2] text-xs font-bold text-[#1F2419] transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="px-5 h-11 rounded-xl bg-[#525A43] hover:bg-[#3B432D] text-xs font-extrabold text-white transition-colors shadow-sm cursor-pointer flex items-center gap-1.5"
                >
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>Simpan Pengguna</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: GANTI PASSWORD PENGGUNA / ADMIN -->
<div id="modal-password-user" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-[#FFFFFF] border border-[#DCD1C2] rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative animate-in fade-in zoom-in duration-150">
        <div class="flex items-center justify-between pb-4 border-b border-[#DCD1C2]/80">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-[#525A43] text-white flex items-center justify-center shadow-xs">
                    <span class="material-symbols-outlined text-xl">lock_reset</span>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-[#1A2016]">Ganti Password</h3>
                    <p class="text-xs text-[#5C6454]" id="modal-user-target-info">-</p>
                </div>
            </div>
            <button type="button" onclick="closePasswordModal()" class="text-[#878c7f] hover:text-[#1A2016] cursor-pointer p-1">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <form id="form-update-password" action="" method="POST" class="space-y-4 pt-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-[#2c3325] mb-1">Password Baru</label>
                <input 
                    type="password" 
                    name="password" 
                    placeholder="Minimal 6 karakter..." 
                    class="w-full h-11 bg-[#FDFBF7] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]" 
                    required 
                    minlength="6"
                >
            </div>

            <div>
                <label class="block text-xs font-bold text-[#2c3325] mb-1">Konfirmasi Password Baru</label>
                <input 
                    type="password" 
                    name="password_confirmation" 
                    placeholder="Ketik ulang password baru..." 
                    class="w-full h-11 bg-[#FDFBF7] border border-[#DCD1C2] px-3.5 rounded-xl text-xs font-semibold text-[#1A2016] focus:outline-none focus:border-[#525A43]" 
                    required 
                    minlength="6"
                >
            </div>

            <div class="pt-3 flex items-center justify-end gap-2.5">
                <button 
                    type="button" 
                    onclick="closePasswordModal()" 
                    class="px-4 h-11 rounded-xl bg-[#F4EFE6] hover:bg-[#EAE1D4] border border-[#DCD1C2] text-xs font-bold text-[#1F2419] transition-colors cursor-pointer"
                >
                    Batal
                </button>
                <button 
                    type="submit" 
                    class="px-5 h-11 rounded-xl bg-[#525A43] hover:bg-[#3B432D] text-xs font-extrabold text-white transition-colors shadow-sm cursor-pointer flex items-center gap-1.5"
                >
                    <span class="material-symbols-outlined text-base">key</span>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAddUserModal() {
        document.getElementById('modal-add-user').classList.remove('hidden');
    }

    function closeAddUserModal() {
        document.getElementById('modal-add-user').classList.add('hidden');
    }

    function openPasswordModal(id, name, email) {
        document.getElementById('modal-user-target-info').innerText = `${name} (${email})`;
        const form = document.getElementById('form-update-password');
        form.action = `/admin/users/${id}/password`;
        document.getElementById('modal-password-user').classList.remove('hidden');
    }

    function closePasswordModal() {
        document.getElementById('modal-password-user').classList.add('hidden');
    }
</script>
@endpush
@endsection
