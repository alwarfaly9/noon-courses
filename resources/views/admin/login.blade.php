<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - noon</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);
            min-height: 100vh;
        }
        /* Decorative circles */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            pointer-events: none;
        }
        body::before { width: 400px; height: 400px; top: -120px; left: -120px; }
        body::after  { width: 300px; height: 300px; bottom: -80px; right: -80px; }

        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.97);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(87,36,122,0.35);
        }
        .input-group { position: relative; }
        .input-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        .input-group input { padding-right: 45px; }
        .input-group input:focus {
            outline: none;
            border-color: #823DBB;
            box-shadow: 0 0 0 3px rgba(130,61,187,0.15);
        }
        .btn-login {
            background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(87,36,122,0.4);
        }
        .logo-circle {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 12px 30px rgba(87,36,122,0.35);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="login-card p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="logo-circle">
                <img src="/logo.png" alt="noon" style="width:60px;height:60px;object-fit:contain;">
            </div>
            <h1 class="text-4xl font-extrabold" style="color:#57247A;">noon</h1>
            <p class="text-gray-500 mt-2 text-sm">لوحة تحكم المسؤول</p>
        </div>

        @if(session('error') || $errors->any())
            <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-xl mb-4 flex items-center">
                <i class="fas fa-exclamation-circle ml-2"></i>
                {{ session('error') ?? $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/admin/login" class="space-y-5">
            @csrf
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">البريد الإلكتروني</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" required
                           class="border border-gray-300 rounded-xl w-full py-3 px-4 text-gray-700 leading-tight transition-all"
                           placeholder="أدخل بريدك الإلكتروني"
                           value="{{ old('email') }}">
                </div>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2">كلمة المرور</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required
                           class="border border-gray-300 rounded-xl w-full py-3 px-4 text-gray-700 leading-tight transition-all"
                           placeholder="أدخل كلمة المرور">
                </div>
            </div>
            <button type="submit" class="btn-login text-white font-bold py-3 px-4 rounded-xl w-full flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                <span>تسجيل الدخول</span>
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-gray-400">
            <p><i class="fas fa-shield-alt ml-1"></i> منصة تعليمية متكاملة — لوحة تحكم المسؤولين فقط</p>
        </div>
    </div>
</body>
</html>
