<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Show admin login page.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->hasRole('admin')) {
                return redirect('/admin/dashboard');
            }
            if ($user->hasRole('teacher')) {
                return redirect()->route('teacher.login');
            }
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
        return view('admin.login');
    }

    /**
     * Handle admin login.
     */
    public function login(AdminLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->hasAnyRole(['admin', 'teacher'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withInput()->with('error', 'هذا الحساب لا يملك صلاحية الدخول للوحة التحكم');
            }

            if ($user->hasRole('teacher')) {
                // Teachers have their own login page
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('teacher.login')->with('error', 'استخدم بوابة المعلم لتسجيل الدخول');
            }
            return redirect()->intended('/admin/dashboard');
        }

        return back()->withInput()->with('error', 'خطأ في البريد الإلكتروني أو كلمة المرور');
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}
