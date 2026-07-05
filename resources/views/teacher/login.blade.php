<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول المعلم - noon</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.97);
        }
        .input-group { position: relative; }
        .input-group i { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #6b7280; }
        .input-group input { padding-right: 45px; }
        .btn-login {
            background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(87,36,122,0.4);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="login-card p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="text-center mb-8">
            <div style="width:80px;height:80px;background:linear-gradient(135deg,#57247A,#823DBB);border-radius:20px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 12px 30px rgba(87,36,122,0.35);overflow:hidden;">
                <img src="/logo.png" alt="noon" style="width:64px;height:64px;object-fit:contain;">
            </div>
            <h1 class="text-4xl font-extrabold" style="color:#57247A;">noon</h1>
            <p class="text-gray-500 mt-2 text-sm">بوابة المعلم</p>
        </div>

        @if(session('error') || $errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center">
            <i class="fas fa-exclamation-circle ml-2"></i>
            {{ session('error') ?? $errors->first() }}
        </div>
        @endif

        @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
        @endif

        <form method="POST" action="{{ route('teacher.login.submit') }}">
            @csrf
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="shadow appearance-none border border-gray-300 rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                           placeholder="أدخل بريدك الإلكتروني">
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">كلمة المرور</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required
                           class="shadow appearance-none border border-gray-300 rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-200"
                           placeholder="أدخل كلمة المرور">
                </div>
            </div>
            <button type="submit"
                    class="btn-login text-white font-bold py-3 px-4 rounded-lg w-full flex items-center justify-center gap-2">
                <i class="fas fa-sign-in-alt"></i>
                <span>تسجيل الدخول</span>
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-500">
            <i class="fas fa-info-circle ml-1"></i>
            هذه البوابة مخصصة للمعلمين المسجلين فقط.
            <br>
            <a href="{{ route('admin.login') }}" class="text-purple-400 hover:text-purple-600 text-xs mt-2 inline-block">
                دخول المسؤول
            </a>
        </div>
    </div>
</body>
</html>
