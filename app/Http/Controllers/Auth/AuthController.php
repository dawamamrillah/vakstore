<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if (Auth::user()->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))
                    ->with('success', 'Selamat datang di Admin Control Center VAKSTORE.');
            }

            return redirect()->intended(route('user.dashboard'))
                ->with('success', 'Selamat datang kembali, '.Auth::user()->name.'!');
        }

        return back()->withErrors([
            'email' => 'Kombinasi email dan kata sandi tidak cocok.',
        ])->onlyInput('email');
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => 'user',
            'status' => 'active',
            'password' => Hash::make($request->password),
        ]);

        // Create initial wallet
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        Auth::login($user);

        return redirect()->route('user.dashboard')
            ->with('success', 'Akun berhasil dibuat! Selamat bergabung di VAKSTORE.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Anda telah berhasil keluar.');
    }

    /**
     * Quick Demo Switch (for instant evaluation during development)
     */
    public function demoLogin(string $type)
    {
        if ($type === 'admin') {
            $admin = User::where('role', 'admin')->first();
            if ($admin) {
                Auth::login($admin);

                return redirect()->route('admin.dashboard')->with('success', 'Beralih ke sesi Super Administrator.');
            }
        } else {
            $user = User::where('role', 'user')->first();
            if ($user) {
                Auth::login($user);

                return redirect()->route('user.dashboard')->with('success', 'Beralih ke sesi Pengguna VIP ('.$user->name.').');
            }
        }

        return redirect()->route('home');
    }
}
