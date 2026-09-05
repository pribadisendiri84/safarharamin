<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminCaptcha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request, AdminCaptcha $captcha)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'captcha' => ['required', 'string', 'size:6'],
        ]);

        if (! $captcha->verify($request, $credentials['captcha'])) {
            throw ValidationException::withMessages([
                'captcha' => 'Kode keamanan salah atau sudah kedaluwarsa. Muat ulang gambar lalu coba lagi.',
            ]);
        }

        unset($credentials['captcha']);

        $user = User::withTrashed()->where('email', $credentials['email'])->first();

        if ($user?->trashed()) {
            return back()->withErrors([
                'email' => 'Akun ini sudah dihapus. Minta superadmin memulihkan akses.',
            ])->onlyInput('email');
        }

        if ($user?->isLoginLocked()) {
            return back()->withErrors([
                'email' => 'Akun terkunci setelah tiga kali login gagal. Hubungi superadmin untuk membuka akses.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var User $authenticated */
            $authenticated = Auth::user();
            $authenticated->unlockLogin();
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        $locked = $user?->registerFailedLogin() ?? false;

        return back()->withErrors([
            'email' => $locked
                ? 'Akun terkunci setelah tiga kali login gagal. Hubungi superadmin untuk membuka akses.'
                : 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
