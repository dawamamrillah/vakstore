<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Wallet\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function index(Request $request)
    {
        $query = User::with('wallet');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function adjustBalance(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'action_type' => 'required|in:add,subtract',
            'amount' => 'required|numeric|min:1000',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $amount = (float) $request->amount;
            $reason = $request->reason;
            $oldBalance = $user->balance;

            if ($request->action_type === 'add') {
                $this->walletService->credit(
                    $user,
                    $amount,
                    'admin_adjustment',
                    'ADJ-'.date('Ymd').'-'.rand(100, 999),
                    'Penambahan saldo oleh Admin: '.$reason
                );
            } else {
                $this->walletService->debit(
                    $user,
                    $amount,
                    'admin_adjustment',
                    'ADJ-'.date('Ymd').'-'.rand(100, 999),
                    'Pengurangan saldo oleh Admin: '.$reason
                );
            }

            $user->load('wallet');

            AuditService::log(
                'adjust_user_balance',
                User::class,
                $user->id,
                ['balance' => $oldBalance],
                ['balance' => $user->balance, 'reason' => $reason, 'type' => $request->action_type, 'amount' => $amount],
                auth()->user()
            );

            return back()->with('success', 'Saldo pengguna '.$user->name.' berhasil disesuaikan!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:admin,user',
            'status' => 'required|in:active,suspended',
            'password' => 'required|string|min:6',
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'status' => $request->status,
                'password' => Hash::make($request->password),
            ]);

            $this->walletService->getWallet($user);

            AuditService::log(
                'create_user',
                User::class,
                $user->id,
                [],
                ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
                auth()->user()
            );

            return back()->with('success', 'Pengguna baru '.$user->name.' (Role: '.strtoupper($user->role).') berhasil ditambahkan!');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Gagal menambahkan pengguna: '.$e->getMessage());
        }
    }

    public function updatePassword(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            AuditService::log(
                'change_user_password',
                User::class,
                $user->id,
                [],
                ['email' => $user->email],
                auth()->user()
            );

            return back()->with('success', 'Password pengguna '.$user->name.' berhasil diperbarui!');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal memperbarui password: '.$e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if (auth()->check() && auth()->id() == $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang login!');
        }

        try {
            $userName = $user->name;
            $userEmail = $user->email;

            AuditService::log(
                'delete_user',
                User::class,
                $user->id,
                ['name' => $userName, 'email' => $userEmail],
                [],
                auth()->user()
            );

            $user->delete();

            return back()->with('success', 'Pengguna '.$userName.' ('.$userEmail.') berhasil dihapus dari sistem.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal menghapus pengguna: '.$e->getMessage());
        }
    }
}
