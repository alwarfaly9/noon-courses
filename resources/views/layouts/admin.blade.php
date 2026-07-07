<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة التحكم') - noon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --brand-primary: #57247A;
            --brand-light: #823DBB;
            --brand-accent: #C59BD7;
        }
        .sidebar {
            min-height: calc(100vh - 60px);
        }
        .sidebar a {
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background-color: var(--brand-primary);
            color: #fff;
            transform: translateX(5px);
        }
        .sidebar a.active {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-light) 100%);
            border-left: 4px solid var(--brand-accent);
            color: #fff !important;
        }
        .sidebar a.active i, .sidebar a:hover i {
            color: #fff;
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(87, 36, 122, 0.15);
        }
        .stat-card {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-light) 100%);
        }
        .stat-card-blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        .stat-card-purple {
            background: linear-gradient(135deg, #823DBB 0%, #57247A 100%);
        }
        .stat-card-orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        table {
            border-collapse: separate;
            border-spacing: 0;
        }
        table tbody tr {
            transition: background-color 0.2s ease;
        }
        table tbody tr:hover {
            background-color: #f5f0fa;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-light) 100%);
            color: #fff;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(87,36,122,0.3);
        }
        .btn-danger {
            background-color: #ef4444;
            transition: all 0.3s ease;
        }
        .btn-danger:hover {
            background-color: #dc2626;
            transform: scale(1.05);
        }
        .noon-logo {
            width: 36px; height: 36px;
            border-radius: 10px;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
        }
        .noon-logo img { width: 36px; height: 36px; object-fit: contain; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navbar -->
    <nav class="text-white p-4 shadow-lg" style="background: linear-gradient(135deg, #57247A 0%, #823DBB 100%);">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3 space-x-reverse">
                <div class="noon-logo"><img src="/logo.png" alt="noon"></div>
                <h1 class="text-2xl font-bold tracking-wide">noon</h1>
            </div>
            <div class="flex items-center space-x-4 space-x-reverse">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i class="fas fa-user-circle"></i>
                    <span>{{ Auth::user()->name ?? 'Admin' }}</span>
                </div>
                <form method="POST" action="/admin/logout" class="inline">
                    @csrf
                    <button type="submit" class="hover:bg-white hover:bg-opacity-20 px-4 py-2 rounded flex items-center space-x-2 space-x-reverse transition-all" style="background:rgba(255,255,255,0.15)">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-4 flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-white rounded-lg shadow-lg sidebar p-4">
            <ul class="space-y-2">
                <li>
                    <a href="/admin/dashboard" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/dashboard') ? 'active text-white' : '' }}">
                        <i class="fas fa-home w-6"></i>
                        <span class="mr-3">الرئيسية</span>
                    </a>
                </li>
                
                @if(Auth::user()->hasRole('admin'))
                <li>
                    <a href="/admin/users" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/users*') ? 'active text-white' : '' }}">
                        <i class="fas fa-users w-6"></i>
                        <span class="mr-3">المستخدمين</span>
                    </a>
                </li>
                @endif

                <li>
                    <a href="/admin/courses" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/courses*') ? 'active text-white' : '' }}">
                        <i class="fas fa-book w-6"></i>
                        <span class="mr-3">{{ Auth::user()->hasRole('teacher') ? 'دوراتي' : 'الدورات' }}</span>
                    </a>
                </li>

                @if(Auth::user()->hasRole('admin'))
                <li>
                    <a href="/admin/categories" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/categories*') ? 'active text-white' : '' }}">
                        <i class="fas fa-folder w-6"></i>
                        <span class="mr-3">الفئات</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/transactions" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/transactions*') ? 'active text-white' : '' }}">
                        <i class="fas fa-money-bill-wave w-6"></i>
                        <span class="mr-3">المعاملات</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/credit-cards" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/credit-cards*') ? 'active text-white' : '' }}">
                        <i class="fas fa-credit-card w-6"></i>
                        <span class="mr-3">كروت الرصيد</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/coupons" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/coupons*') ? 'active text-white' : '' }}">
                        <i class="fas fa-ticket-alt w-6"></i>
                        <span class="mr-3">الكوبونات</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/support" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/support*') ? 'active text-white' : '' }}">
                        <i class="fas fa-headset w-6"></i>
                        <span class="mr-3">الدعم الفني</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/withdraw-requests" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/withdraw-requests*') ? 'active text-white' : '' }}">
                        <i class="fas fa-money-bill-wave w-6"></i>
                        <span class="mr-3">طلبات السحب</span>
                        @php $pendingCount = \App\Models\WithdrawRequest::where('status','pending')->count(); @endphp
                        @if($pendingCount > 0)
                        <span class="mr-auto bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ $pendingCount }}</span>
                        @endif
                    </a>
                </li>
                <li>
                    <a href="/admin/reports" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/reports*') ? 'active text-white' : '' }}">
                        <i class="fas fa-chart-bar w-6"></i>
                        <span class="mr-3">التقارير</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/certificates" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/certificates*') ? 'active text-white' : '' }}">
                        <i class="fas fa-certificate w-6"></i>
                        <span class="mr-3">الشهادات</span>
                    </a>
                </li>

                {{-- Engagement Platform --}}
                <li class="pt-3 border-t border-gray-200">
                    <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wide">التفاعل</div>
                </li>
                <li>
                    <a href="/admin/achievements" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/achievements*') ? 'active text-white' : '' }}">
                        <i class="fas fa-trophy w-6"></i>
                        <span class="mr-3">الشارات</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/campaigns" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/campaigns*') ? 'active text-white' : '' }}">
                        <i class="fas fa-bullhorn w-6"></i>
                        <span class="mr-3">الحملات</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/stories" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/stories*') ? 'active text-white' : '' }}">
                        <i class="fas fa-story w-6"></i>
                        <span class="mr-3">القصص</span>
                    </a>
                </li>
                <li>
                    <a href="/admin/referrals" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/referrals*') ? 'active text-white' : '' }}">
                        <i class="fas fa-share-alt w-6"></i>
                        <span class="mr-3">الإحالات</span>
                    </a>
                </li>

                <li>
                    <a href="/admin/settings" class="flex items-center px-4 py-3 text-gray-700 rounded {{ request()->is('admin/settings*') ? 'active text-white' : '' }}">
                        <i class="fas fa-cog w-6"></i>
                        <span class="mr-3">إعدادات المنصة</span>
                    </a>
                </li>
                @endif
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 mr-4">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 flex items-center justify-between">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @stack('scripts')
</body>
</html>
