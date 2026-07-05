<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('teacher')) {
                return redirect()->route('teacher.dashboard');
            }
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }
            Auth::logout();
        }
        return view('teacher.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => 'خطأ في البريد الإلكتروني أو كلمة المرور',
            ]);
        }

        $user = Auth::user();

        if (!$user->hasRole('teacher')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withInput()->with('error', 'هذا الحساب ليس حساب معلم');
        }

        $request->session()->regenerate();
        return redirect()->route('teacher.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('teacher.login');
    }
}
