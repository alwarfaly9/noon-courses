<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - noon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { font-family: 'Tajawal', sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            top: -200px; right: -200px;
            pointer-events: none;
            border-radius: 50%;
        }
        body::after {
            content: '';
            position: fixed;
            width: 450px; height: 450px;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            bottom: -150px; left: -150px;
            pointer-events: none;
            border-radius: 50%;
        }
        .login-card {
            backdrop-filter: blur(16px);
            background: rgba(255, 255, 255, 0.97);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(87,36,122,0.35);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }
        .brand-circle {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 12px 30px rgba(87,36,122,0.35);
            overflow: hidden;
        }
        .brand-circle img {
            width: 56px; height: 56px;
            object-fit: contain;
        }
        .input-group { position: relative; }
        .input-group i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.9rem;
            z-index: 2;
        }
        .input-group input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 0.85rem;
            padding-right: 2.75rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.9rem;
            color: #374151;
            transition: all 0.2s ease;
            background: #fafafa;
        }
        .input-group input:focus {
            outline: none;
            border-color: #823DBB;
            box-shadow: 0 0 0 3px rgba(130,61,187,0.12);
            background: #fff;
        }
        .input-group input::placeholder { color: #b0b7c3; }
        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(87,36,122,0.4);
        }
        .btn-login:active { transform: translateY(0); }
        @media (max-width: 480px) {
            .login-card { padding: 1.75rem; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-7">
            <div class="brand-circle">
                <img src="{{ asset('images/noon.png') }}" alt="noon">
            </div>
            <h1 style="font-size: 1.75rem; font-weight: 900; color: #57247A; letter-spacing: -0.02em; line-height: 1.2;">noon</h1>
            <p style="color: #9ca3af; margin-top: 0.25rem; font-size: 0.85rem;">لوحة تحكم المسؤول</p>
        </div>

        @if(session('error') || $errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1rem;border-radius:12px;margin-bottom:1.25rem;display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;">
                <i class="fas fa-exclamation-circle"></i>
                {{ session('error') ?? $errors->first() }}
            </div>
        @endif

        <form method="POST" action="/admin/login">
            @csrf
            <div style="margin-bottom: 1.25rem;">
                <label style="display:block;color:#374151;font-size:0.85rem;font-weight:700;margin-bottom:0.5rem;">البريد الإلكتروني</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" required
                           placeholder="أدخل بريدك الإلكتروني"
                           value="{{ old('email') }}">
                </div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block;color:#374151;font-size:0.85rem;font-weight:700;margin-bottom:0.5rem;">كلمة المرور</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required
                           placeholder="أدخل كلمة المرور">
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <span>تسجيل الدخول</span>
            </button>
        </form>

        <div style="margin-top: 1.75rem; text-align: center; font-size: 0.75rem; color: #b0b7c3;">
            <i class="fas fa-shield-alt" style="margin-left:0.25rem;"></i>
            منصة تعليمية متكاملة — لوحة تحكم المسؤولين فقط
        </div>
    </div>
</body>
</html>
