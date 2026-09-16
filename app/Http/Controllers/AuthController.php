<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;

class AuthController extends Controller
{
    public function login() {
        return view('auth.login');
    }

    public function dologin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (auth()->attempt($credentials)) {

            $request->session()->regenerate();

            $role = auth()->user()->role_id;

            if ($role === 1) {
                // Admin
                return redirect()->intended('/admin');
            } elseif ($role === 2) {
                // AL
                return redirect()->intended('/al');
            } elseif ($role === 3) {
                // AH
                return redirect()->intended('/ah');
            } else {
                // KP
                return redirect()->intended('/kp');
            }
        }

        return back()->with('error', 'Email atau password salah');
    }

    public function logout(Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
