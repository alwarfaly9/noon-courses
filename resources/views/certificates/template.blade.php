<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>شهادة إتمام الدورة - Certificate of Completion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #ffffff;
            color: #1a1a2e;
        }
        .page {
            width: 100%;
            min-height: 560px;
            padding: 28px 50px;
            border: 14px double #2d6a4f;
            position: relative;
        }
        .inner-border {
            position: absolute;
            top: 7px; left: 7px; right: 7px; bottom: 7px;
            border: 2px solid #74c69d;
        }
        .header {
            text-align: center;
            margin-bottom: 14px;
        }
        .logo-img {
            max-height: 65px;
            max-width: 180px;
        }
        .platform-name-text {
            font-size: 20px;
            color: #2d6a4f;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .divider {
            width: 55%;
            height: 2px;
            background: #74c69d;
            margin: 10px auto;
        }
        .cert-title-ar {
            font-size: 28px;
            font-weight: bold;
            color: #1b4332;
            text-align: center;
            direction: rtl;
            margin-bottom: 3px;
        }
        .cert-title-en {
            font-size: 13px;
            color: #74c69d;
            text-align: center;
            letter-spacing: 4px;
            text-transform: uppercase;
        }
        .body-ar {
            text-align: center;
            font-size: 13px;
            color: #555;
            direction: rtl;
            margin: 18px 0 6px 0;
        }
        .name-wrapper {
            text-align: center;
            margin: 6px 0 4px 0;
        }
        .student-name {
            font-size: 26px;
            font-weight: bold;
            color: #1b4332;
            direction: rtl;
            display: inline-block;
            border-bottom: 2px solid #52b788;
            padding: 0 36px 5px 36px;
        }
        .body-ar-2 {
            text-align: center;
            font-size: 13px;
            color: #555;
            direction: rtl;
            margin: 14px 0 6px 0;
        }
        .course-name {
            font-size: 18px;
            font-weight: bold;
            color: #1b4332;
            direction: rtl;
            text-align: center;
            background: #f0faf4;
            border-radius: 6px;
            padding: 10px 24px;
            margin: 0 30px;
        }
        .meta-row {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 16px;
            direction: rtl;
        }
        .footer {
            margin-top: 22px;
            border-top: 1px solid #d8f3dc;
            padding-top: 10px;
            text-align: center;
        }
        .footer-name {
            font-size: 13px;
            color: #2d6a4f;
            font-weight: bold;
        }
        .cert-id {
            font-size: 9px;
            color: #aaa;
            margin-top: 5px;
            direction: ltr;
        }
        .corner {
            position: absolute;
            width: 30px;
            height: 30px;
            border-color: #2d6a4f;
            border-style: solid;
        }
        .corner-tl { top: 18px; right: 18px; border-width: 3px 0 0 3px; }
        .corner-tr { top: 18px; left: 18px;  border-width: 3px 3px 0 0; }
        .corner-bl { bottom: 18px; right: 18px; border-width: 0 0 3px 3px; }
        .corner-br { bottom: 18px; left: 18px;  border-width: 0 3px 3px 0; }
    </style>
</head>
<body>
<div class="page">
    <div class="inner-border"></div>
    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>

    <div class="header">
        @if(!empty($platformLogo))
            <img src="{{ $platformLogo }}" class="logo-img" alt="{{ $platformName }}">
        @else
            <div class="platform-name-text">{{ $platformName }}</div>
        @endif
    </div>

    <div class="divider"></div>
    <div class="cert-title-ar">شهادة إتمام الدورة</div>
    <div class="cert-title-en">Certificate of Completion</div>
    <div class="divider"></div>

    <div class="body-ar">تُقر منصة {{ $platformName }} بأن</div>

    <div class="name-wrapper">
        <span class="student-name">{{ $user->name }}</span>
    </div>

    <div class="body-ar-2">قد أتم بنجاح متطلبات الدورة التدريبية</div>

    <div class="course-name">{{ $course->title }}</div>

    <div class="meta-row">
        بتاريخ {{ $date }}
        @if(isset($course->teacher) && $course->teacher)
            &nbsp;&nbsp;|&nbsp;&nbsp; المحاضر: {{ $course->teacher->name }}
        @endif
    </div>

    <div class="footer">
        <div class="footer-name">{{ $platformName }}</div>
        <div class="cert-id">
            Certificate ID: {{ $certificate->certificate_id }}
            &nbsp;|&nbsp;
            Verify: {{ url('/api/certificates/verify/' . $certificate->certificate_id) }}
        </div>
    </div>
</div>
</body>
</html>
