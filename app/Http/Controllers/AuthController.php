<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Mengirim flash message sukses saat masuk dashboard
            return redirect()->intended('dashboard')
                ->with('toast_success', 'Selamat Datang Kembali! Otentikasi Berhasil.');
        }

        // Mengirim flash message warning jika kredensial salah
        return back()->with('toast_warning', 'Akses Ditolak! Email atau password salah.')
            ->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Mengirim flash message info/warning saat berhasil keluar
        return redirect('/login')
            ->with('toast_warning', 'Anda telah keluar dari sistem manajemen aset.');
    }
}