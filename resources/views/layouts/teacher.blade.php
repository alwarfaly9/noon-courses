<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة المعلم') - noon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar { min-height: calc(100vh - 60px); }
        .sidebar a { transition: all 0.3s ease; }
        .sidebar a:hover { background-color: #57247A; color: #fff; transform: translateX(5px); }
        .sidebar a.active { background: linear-gradient(135deg, #57247A 0%, #823DBB 100%); border-left: 4px solid #C59BD7; color: #fff !important; }
        .card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        table { border-collapse: separate; border-spacing: 0; }
        table tbody tr { transition: background-color 0.2s ease; }
        table tbody tr:hover { background-color: #f3f4f6; }
    </style>
    @stack('head')
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="text-white p-4 shadow-lg" style="background: linear-gradient(135deg, #57247A 0%, #823DBB 100%)">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3 space-x-reverse">
                <img src="/logo.png" alt="noon" style="width:36px;height:36px;object-fit:contain;border-radius:10px;">
                <div>
                    <h1 class="text-xl font-bold tracking-wide">noon — المعلم</h1>
                </div>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i class="fas fa-user-circle"></i>
                    <span>{{ Auth::user()->name ?? 'معلم' }}</span>
                </div>
                <form method="POST" action="/teacher/logout" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded flex items-center space-x-2 space-x-reverse transition-all hover:bg-white hover:bg-opacity-20" style="background:rgba(255,255,255,0.15)">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-4 flex gap-4 px-4 pb-10">
        <!-- Sidebar -->
        <aside class="w-64 flex-shrink-0 bg-white rounded-lg shadow-lg sidebar p-4">
            <ul class="space-y-2">
                <li>
                    <a href="/teacher/dashboard" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('teacher/dashboard') ? 'active text-white' : '' }}">
                        <i class="fas fa-tachometer-alt w-6"></i>
                        <span class="mr-3">لوحة التحكم</span>
                    </a>
                </li>
                <li>
                    <a href="/teacher/courses" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('teacher/courses*') ? 'active text-white' : '' }}">
                        <i class="fas fa-graduation-cap w-6"></i>
                        <span class="mr-3">دوراتي</span>
                    </a>
                </li>
                <li>
                    <a href="/teacher/withdraw-requests" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('teacher/withdraw-requests*') ? 'active text-white' : '' }}">
                        <i class="fas fa-money-bill-wave w-6"></i>
                        <span class="mr-3">طلبات السحب</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0">
            @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                <span class="text-green-800">{{ session('success') }}</span>
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                <span class="text-red-800">{{ session('error') }}</span>
            </div>
            @endif
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
