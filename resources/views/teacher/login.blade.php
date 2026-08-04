<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - noon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-[#f5f6fa] flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Decorative brand glow -->
    <div class="pointer-events-none absolute -top-32 -start-32 w-96 h-96 rounded-full bg-brand-100/50 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-32 -end-32 w-96 h-96 rounded-full bg-brand-200/40 blur-3xl" aria-hidden="true"></div>

    <div class="w-full max-w-[420px] relative z-10">
        <div class="bg-white rounded-2xl shadow-card-xl border border-[#e9ebf1] p-8 sm:p-10">
            <div class="text-center mb-8">
                <img src="{{ asset('images/noon.png') }}" alt="noon"
                     class="w-16 h-16 mx-auto mb-4 object-contain"
                     style="filter: brightness(0) saturate(100%) invert(19%) sepia(69%) saturate(3166%) hue-rotate(270deg) brightness(60%) contrast(95%);">
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">noon</h1>
                <p class="text-sm text-gray-500 mt-1">بوابة المعلم</p>
            </div>

            @if(session('error') || $errors->any())
                <div class="alert-danger mb-5" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ session('error') ?? $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="/teacher/login" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute start-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                        <input id="email" type="email" name="email" required
                               placeholder="أدخل بريدك الإلكتروني"
                               value="{{ old('email') }}"
                               class="form-input ps-10">
                    </div>
                </div>
                <div>
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute start-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none"></i>
                        <input id="password" type="password" name="password" required
                               placeholder="أدخل كلمة المرور"
                               class="form-input ps-10">
                    </div>
                </div>
                <button type="submit" class="btn-primary w-full">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>تسجيل الدخول</span>
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-[#eef0f4] text-center text-xs text-gray-400">
                <i class="fas fa-chalkboard-teacher ms-1"></i>
                منصة تعليمية متكاملة — بوابة المعلمين
            </div>
        </div>
    </div>
</body>
</html>