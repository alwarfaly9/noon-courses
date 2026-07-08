@extends('layouts.admin')

@section('title', 'لوحة التحكم')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="welcome-header">
            مرحباً، {{ Auth::user()->name }}
            <span class="welcome-subtitle">نظرة عامة على المنصة</span>
        </h1>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="stat-card-gradient-primary">
            <div class="stat-label"><i class="fas fa-users"></i> {{ Auth::user()->hasRole('admin') ? 'إجمالي المستخدمين' : 'إجمالي الطلاب' }}</div>
            <div class="stat-value">{{ Auth::user()->hasRole('admin') ? $stats['total_users'] : ($stats['total_students'] ?? 0) }}</div>
            <div class="stat-icon"><i class="fas fa-users"></i></div>
        </div>
        
        <div class="stat-card-gradient-blue">
            <div class="stat-label"><i class="fas fa-book"></i> {{ Auth::user()->hasRole('admin') ? 'الدورات المنشورة' : 'دوراتي المنشورة' }}</div>
            <div class="stat-value">{{ $stats['published_courses'] }}</div>
            <div class="stat-icon"><i class="fas fa-book"></i></div>
        </div>
        
        <div class="stat-card-gradient-orange">
            <div class="stat-label"><i class="fas fa-clock"></i> {{ Auth::user()->hasRole('admin') ? 'الدورات قيد المراجعة' : 'دوراتي قيد المراجعة' }}</div>
            <div class="stat-value">{{ $stats['pending_courses'] }}</div>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
        </div>
        
        <div class="stat-card-gradient-purple">
            <div class="stat-label"><i class="fas fa-money-bill-wave"></i> {{ Auth::user()->hasRole('admin') ? 'إجمالي الإيرادات' : 'إجمالي الأرباح' }}</div>
            <div class="stat-value">{{ number_format($stats[Auth::user()->hasRole('admin') ? 'total_revenue' : 'total_earnings'] ?? 0, 0) }} د.ل</div>
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>

    @if(Auth::user()->hasRole('admin'))
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-chart-line text-emerald-500"></i>
                    الإيرادات الشهرية
                </h3>
            </div>
            <div class="card-body">
                <div class="relative" style="height: 280px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- User Growth Chart -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-chart-bar text-blue-500"></i>
                    نمو المستخدمين
                </h3>
            </div>
            <div class="card-body">
                <div class="relative" style="height: 280px;">
                    <canvas id="userChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 {{ Auth::user()->hasRole('admin') ? 'lg:grid-cols-2' : '' }} gap-6">
        @if(Auth::user()->hasRole('admin'))
        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-exchange-alt text-emerald-500"></i>
                    آخر المعاملات
                </h3>
                <a href="/admin/transactions" class="text-sm font-semibold text-brand hover:text-brand-light transition-colors">
                    عرض الكل <i class="fas fa-arrow-left mr-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table-dash">
                        <thead>
                            <tr>
                                <th>المستخدم</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar bg-brand-100 text-brand text-xs">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">{{ $transaction->user->name }}</span>
                                    </div>
                                </td>
                                <td class="font-semibold text-emerald-600">{{ $transaction->amount }} د.ل</td>
                                <td>
                                    <span class="{{ $transaction->status === 'completed' ? 'badge-success' : 'badge-warning' }}">
                                        <i class="fas fa-{{ $transaction->status === 'completed' ? 'check' : 'clock' }}"></i>
                                        {{ $transaction->status === 'completed' ? 'مكتملة' : $transaction->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state py-8">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                        <p class="empty-state-text">لا توجد معاملات حديثة</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pending Courses -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-exclamation-triangle text-amber-500"></i>
                    الدورات قيد المراجعة
                </h3>
                <a href="/admin/courses?status=pending" class="text-sm font-semibold text-brand hover:text-brand-light transition-colors">
                    عرض الكل <i class="fas fa-arrow-left mr-1"></i>
                </a>
            </div>
            <div class="card-body">
                <div class="space-y-3">
                    @forelse($pendingCourses as $course)
                    <div class="list-item">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-gray-800 text-sm">{{ Str::limit($course->title, 40) }}</div>
                            <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                <i class="fas fa-user"></i>
                                {{ $course->teacher->name }}
                            </div>
                        </div>
                        <div class="flex gap-2 mr-3 flex-shrink-0">
                            <form method="POST" action="/admin/courses/{{ $course->id }}/approve">
                                @csrf
                                <button type="submit" class="btn btn-success btn-xs">
                                    <i class="fas fa-check"></i> موافق
                                </button>
                            </form>
                            <button type="button" onclick="openRejectModal({{ $course->id }}, '{{ addslashes($course->title) }}')" class="btn btn-danger btn-xs">
                                <i class="fas fa-times"></i> رفض
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="empty-state py-8">
                        <div class="empty-state-icon">
                            <i class="fas fa-check-circle" style="color:#10b981;"></i>
                        </div>
                        <p class="empty-state-text">لا توجد دورات قيد المراجعة</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
        @else
        <!-- Recent Students (Teacher View) -->
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-user-graduate text-emerald-500"></i>
                    آخر الطلاب المسجلين
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table-dash">
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>الدورة</th>
                                <th>تاريخ التسجيل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentEnrollments ?? [] as $enrollment)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar bg-brand-100 text-brand text-xs">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">{{ $enrollment->student_name }}</span>
                                    </div>
                                </td>
                                <td class="text-gray-500">{{ $enrollment->course_title }}</td>
                                <td class="text-gray-400">{{ \Carbon\Carbon::parse($enrollment->created_at)->toDateString() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3">
                                    <div class="empty-state py-8">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <p class="empty-state-text">لا يوجد تسجيلات حديثة</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Reject Course Modal -->
<div id="rejectModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-bold text-gray-800">رفض الدورة</h3>
            <button type="button" onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="rejectCourseName" class="text-sm text-gray-500 mb-4"></p>
            <form id="rejectForm" method="POST">
                @csrf
                <label class="form-label">
                    سبب الرفض <span class="text-red-500">*</span>
                </label>
                <textarea name="reason" rows="4" required
                          class="form-textarea"
                          placeholder="اكتب سبباً واضحاً سيُرسَل للمعلم عبر البريد الإلكتروني..."></textarea>
                <div class="flex justify-end gap-3 mt-5">
                    <button type="button" onclick="closeRejectModal()" class="btn-secondary btn-sm">إلغاء</button>
                    <button type="submit" class="btn-danger btn-sm">تأكيد الرفض</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openRejectModal(courseId, courseTitle) {
        document.getElementById('rejectForm').action = '/admin/courses/' + courseId + '/reject';
        document.getElementById('rejectCourseName').textContent = courseTitle;
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejectForm').querySelector('textarea').value = '';
    }
    document.getElementById('rejectModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeRejectModal();
    });

    @if(auth()->user()->hasRole('admin'))
    // Revenue Chart - Line with gradient
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = @json($revenueData);
    
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueData.map(d => d.month),
            datasets: [{
                label: 'الإيرادات (د.ل)',
                data: revenueData.map(d => d.total),
                borderColor: '#823dbb',
                backgroundColor: (ctx) => {
                    const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 280);
                    g.addColorStop(0, 'rgba(130, 61, 187, 0.25)');
                    g.addColorStop(1, 'rgba(130, 61, 187, 0)');
                    return g;
                },
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#823dbb',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 7,
                pointHoverBackgroundColor: '#57247a',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e1e2a',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    cornerRadius: 10,
                    padding: 12,
                    boxPadding: 4,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { font: { family: 'Tajawal', size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Tajawal', size: 11 } }
                }
            }
        }
    });

    // User Growth Chart - Bar with brand colors
    const userCtx = document.getElementById('userChart').getContext('2d');
    const userGrowthData = @json($userData);
    
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: userGrowthData.map(d => d.month),
            datasets: [{
                label: 'مستخدمين جدد',
                data: userGrowthData.map(d => d.count),
                backgroundColor: userGrowthData.map((_, i) => {
                    const colors = ['#57247a', '#823dbb', '#c59bd7', '#a56dbd'];
                    return colors[i % colors.length];
                }),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e1e2a',
                    titleColor: '#fff',
                    bodyColor: '#e2e8f0',
                    cornerRadius: 10,
                    padding: 12,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9', drawBorder: false },
                    ticks: { font: { family: 'Tajawal', size: 11 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Tajawal', size: 11 } }
                }
            }
        }
    });
    @endif
</script>
@endpush
@endsection
