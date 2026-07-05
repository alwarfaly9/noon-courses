<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>رمز التحقق</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; direction:rtl; }
        .container { max-width:520px; margin:40px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.08); }
        .header { background:#1d4ed8; padding:30px; text-align:center; }
        .header h1 { color:#fff; margin:0; font-size:22px; }
        .body { padding:32px; text-align:center; }
        .code { display:inline-block; font-size:40px; font-weight:bold; letter-spacing:10px; color:#1d4ed8; background:#eff6ff; border:2px dashed #93c5fd; border-radius:10px; padding:16px 32px; margin:24px 0; }
        .note { color:#6b7280; font-size:13px; margin-top:16px; }
        .footer { background:#f9fafb; padding:16px; text-align:center; color:#9ca3af; font-size:12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>
        <div class="body">
            <p style="font-size:16px; color:#374151;">مرحباً،</p>
            <p style="color:#374151;">رمز التحقق الخاص بك هو:</p>
            <div class="code">{{ $code }}</div>
            <p class="note">هذا الرمز صالح لمدة <strong>5 دقائق</strong> فقط.<br>إذا لم تطلب هذا الرمز، يمكنك تجاهل هذا البريد.</p>
        </div>
        <div class="footer">© {{ date('Y') }} {{ config('app.name') }} — جميع الحقوق محفوظة</div>
    </div>
</body>
</html>
