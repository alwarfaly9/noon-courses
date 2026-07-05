<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تمت الموافقة على دورتك</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; direction:rtl; }
        .container { max-width:540px; margin:40px auto; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.08); }
        .header { background:linear-gradient(135deg,#7c3aed,#6d28d9); padding:30px; text-align:center; }
        .header h1 { color:#fff; margin:0; font-size:22px; }
        .body { padding:32px; }
        .badge { display:inline-flex; align-items:center; gap:8px; background:#f0fdf4; border:1px solid #86efac; color:#166534; border-radius:8px; padding:12px 20px; font-size:18px; font-weight:bold; margin:20px 0; }
        .course-title { background:#faf5ff; border-right:4px solid #7c3aed; padding:14px 18px; border-radius:6px; font-size:16px; font-weight:bold; color:#4c1d95; margin:16px 0; }
        .cta { display:inline-block; background:#7c3aed; color:#fff; text-decoration:none; padding:12px 28px; border-radius:8px; font-weight:bold; margin-top:16px; }
        .note { color:#6b7280; font-size:13px; margin-top:20px; border-top:1px solid #f3f4f6; padding-top:16px; }
        .footer { background:#f9fafb; padding:16px; text-align:center; color:#9ca3af; font-size:12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>
        <div class="body">
            <p style="font-size:16px;color:#374151;">مرحباً <strong>{{ $course->teacher->name }}</strong>،</p>

            <div class="badge">
                ✅ تمت الموافقة على دورتك ونشرها
            </div>

            <p style="color:#374151;">يسعدنا إعلامك بأن فريق إدارة المنصة قد راجع دورتك وأقرّ نشرها:</p>

            <div class="course-title">{{ $course->title }}</div>

            <p style="color:#374151;">الدورة متاحة الآن للطلاب للتسجيل فيها. يمكنك إدارة محتواها والاطلاع على الإحصائيات من خلال لوحة تحكم المعلم.</p>

            <a href="{{ config('app.url') }}/teacher/courses" class="cta">
                الذهاب إلى لوحة التحكم
            </a>

            <div class="note">
                إذا كان لديك أي سؤال، لا تتردد في التواصل مع فريق الدعم.
            </div>
        </div>
        <div class="footer">© {{ date('Y') }} {{ config('app.name') }} — جميع الحقوق محفوظة</div>
    </div>
</body>
</html>
