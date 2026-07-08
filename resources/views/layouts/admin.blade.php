<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'لوحة التحكم') - noon</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @stack('head')
</head>
<body class="bg-surface min-h-screen">

    <!-- Header -->
    <header class="sticky top-0 z-40 bg-gradient-to-l from-brand to-brand-light text-white shadow-lg">
        <div class="px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" class="lg:hidden text-white/80 hover:text-white transition-colors text-xl p-1">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="/admin/dashboard" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/noon.png') }}" alt="noon"
                         class="w-8 h-8 sm:w-9 sm:h-9 object-contain rounded-lg bg-white/10 p-1"
                         style="filter: brightness(0) invert(1);">
                    <span class="text-lg sm:text-xl font-extrabold tracking-tight">noon</span>
                    <span class="hidden sm:inline text-[10px] sm:text-xs font-medium bg-white/15 px-2 py-0.5 rounded-full">Admin</span>
                </a>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="flex items-center gap-2 text-sm">
                    <div class="header-user-avatar">
                        <i class="fas fa-user-circle text-base sm:text-lg"></i>
                    </div>
                    <span class="hidden sm:inline font-medium text-sm">{{ Auth::user()->name ?? 'Admin' }}</span>
                </div>
                <form method="POST" action="/admin/logout">
                    @csrf
                    <button type="submit" class="header-nav-item">
                        <i class="fas fa-sign-out-alt text-xs sm:text-sm"></i>
                        <span class="hidden sm:inline">تسجيل الخروج</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar Overlay (mobile) -->
        <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-30 hidden lg:hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar"
               class="fixed lg:sticky top-16 right-0 lg:right-auto z-30 w-72 h-[calc(100vh-4rem)]
                      bg-white border-l border-gray-100 shadow-card
                      translate-x-full lg:translate-x-0 transition-transform duration-300 ease-out
                      overflow-y-auto scrollbar-thin flex-shrink-0">
            <!-- Logo Area -->
            <div class="p-5 border-b border-gray-100">
                <a href="/admin/dashboard" class="flex items-center gap-3">
                    <img src="{{ asset('images/noon.png') }}" alt="noon"
                         class="w-12 h-12 object-contain rounded-xl"
                         style="filter: brightness(0) saturate(100%) invert(19%) sepia(69%) saturate(3166%) hue-rotate(270deg) brightness(60%) contrast(95%);">
                    <div class="min-w-0">
                        <div class="text-lg font-extrabold text-brand tracking-tight leading-none">noon</div>
                        <div class="text-[10px] font-medium text-gray-400 mt-0.5">لوحة التحكم</div>
                    </div>
                </a>
            </div>

            <nav class="p-3 space-y-0.5">
                <a href="/admin/dashboard" class="{{ request()->is('admin/dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home w-5 text-center"></i>
                    <span>الرئيسية</span>
                </a>

                @if(Auth::user()->hasRole('admin'))
                <div class="sidebar-section-label mt-4">الإدارة</div>
                <a href="/admin/users" class="{{ request()->is('admin/users*') ? 'active' : '' }}">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>المستخدمين</span>
                </a>
                @endif

                <a href="/admin/courses" class="{{ request()->is('admin/courses*') && !request()->is('admin/courses/create*') && !request()->is('admin/courses/*/edit*') && !request()->is('admin/courses/*/content*') ? 'active' : '' }}">
                    <i class="fas fa-book w-5 text-center"></i>
                    <span>{{ Auth::user()->hasRole('teacher') ? 'دوراتي' : 'الدورات' }}</span>
                </a>

                @if(Auth::user()->hasRole('admin'))
                <a href="/admin/categories" class="{{ request()->is('admin/categories*') ? 'active' : '' }}">
                    <i class="fas fa-folder w-5 text-center"></i>
                    <span>الفئات</span>
                </a>

                <div class="sidebar-section-label mt-4">المالية</div>
                <a href="/admin/transactions" class="{{ request()->is('admin/transactions*') ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave w-5 text-center"></i>
                    <span>المعاملات</span>
                </a>
                <a href="/admin/credit-cards" class="{{ request()->is('admin/credit-cards*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card w-5 text-center"></i>
                    <span>كروت الرصيد</span>
                </a>
                <a href="/admin/coupons" class="{{ request()->is('admin/coupons*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt w-5 text-center"></i>
                    <span>الكوبونات</span>
                </a>
                <a href="/admin/withdraw-requests" class="{{ request()->is('admin/withdraw-requests*') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-usd w-5 text-center"></i>
                    <span>طلبات السحب</span>
                    @php $pendingCount = \App\Models\WithdrawRequest::where('status','pending')->count(); @endphp
                    @if($pendingCount > 0)
                    <span class="mr-auto bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[20px] h-5 flex items-center justify-center px-1">{{ $pendingCount }}</span>
                    @endif
                </a>

                <div class="sidebar-section-label mt-4">الدعم</div>
                <a href="/admin/support" class="{{ request()->is('admin/support*') ? 'active' : '' }}">
                    <i class="fas fa-headset w-5 text-center"></i>
                    <span>الدعم الفني</span>
                </a>

                <div class="sidebar-section-label mt-4">التقارير</div>
                <a href="/admin/reports" class="{{ request()->is('admin/reports*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar w-5 text-center"></i>
                    <span>التقارير</span>
                </a>
                <a href="/admin/certificates" class="{{ request()->is('admin/certificates*') ? 'active' : '' }}">
                    <i class="fas fa-certificate w-5 text-center"></i>
                    <span>الشهادات</span>
                </a>

                <div class="sidebar-section-label mt-4">التفاعل</div>
                <a href="/admin/achievements" class="{{ request()->is('admin/achievements*') ? 'active' : '' }}">
                    <i class="fas fa-trophy w-5 text-center"></i>
                    <span>الشارات</span>
                </a>
                <a href="/admin/campaigns" class="{{ request()->is('admin/campaigns*') ? 'active' : '' }}">
                    <i class="fas fa-bullhorn w-5 text-center"></i>
                    <span>الحملات</span>
                </a>
                <a href="/admin/stories" class="{{ request()->is('admin/stories*') ? 'active' : '' }}">
                    <i class="fas fa-book-open w-5 text-center"></i>
                    <span>القصص</span>
                </a>
                <a href="/admin/referrals" class="{{ request()->is('admin/referrals*') ? 'active' : '' }}">
                    <i class="fas fa-share-alt w-5 text-center"></i>
                    <span>الإحالات</span>
                </a>

                <div class="sidebar-section-label mt-4">النظام</div>
                <a href="/admin/settings" class="{{ request()->is('admin/settings*') ? 'active' : '' }}">
                    <i class="fas fa-cog w-5 text-center"></i>
                    <span>إعدادات المنصة</span>
                </a>
                @endif
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 p-4 sm:p-6 lg:p-8 animate-fade-in">
            @if(session('success'))
                <div class="alert-success mb-5 animate-slide-up" role="alert">
                    <i class="fas fa-check-circle text-emerald-600"></i>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-emerald-600/60 hover:text-emerald-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert-danger mb-5 animate-slide-up" role="alert">
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button onclick="this.parentElement.remove()" class="text-red-600/60 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');

        if (toggle && sidebar && overlay) {
            toggle.addEventListener('click', function() {
                sidebar.classList.toggle('translate-x-full');
                sidebar.classList.toggle('translate-x-0');
                overlay.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.add('translate-x-full');
                sidebar.classList.remove('translate-x-0');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            });
        }

        // Close sidebar on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar && !sidebar.classList.contains('translate-x-full') && window.innerWidth < 1024) {
                sidebar.classList.add('translate-x-full');
                sidebar.classList.remove('translate-x-0');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });

        // Auto-close alert after 5 seconds
        document.querySelectorAll('.alert-success, .alert-danger').forEach(function(alert) {
            setTimeout(function() {
                if (alert.parentElement) alert.remove();
            }, 5000);
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
