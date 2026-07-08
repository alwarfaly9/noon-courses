@extends('layouts.teacher')
@section('title', 'لوحة التحكم')
@section('content')
<div class="space-y-6">
    <div class="page-header">
        <h1 class="welcome-header">
            مرحباً، {{ Auth::user()->name }}
            <span class="welcome-subtitle">نظرة عامة على أدائك</span>
        </h1>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-brand"></i>
                </div>
            </div>
            <div class="stat-label">إجمالي الدورات</div>
            <div class="stat-value text-brand">{{ $totalCourses }}</div>
        </div>
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-check-circle text-emerald-500"></i>
                </div>
            </div>
            <div class="stat-label">دورات منشورة</div>
            <div class="stat-value text-emerald-600">{{ $publishedCourses }}</div>
        </div>
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <i class="fas fa-users text-blue-500"></i>
                </div>
            </div>
            <div class="stat-label">إجمالي الطلاب</div>
            <div class="stat-value text-blue-600">{{ $totalStudents }}</div>
        </div>
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-orange-500"></i>
                </div>
            </div>
            <div class="stat-label">أرباحك الكلية</div>
            <div class="stat-value text-orange-600">{{ number_format($totalEarnings, 0) }} د.ل</div>
        </div>
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-50 flex items-center justify-center">
                    <i class="fas fa-certificate text-cyan-500"></i>
                </div>
            </div>
            <div class="stat-label">الشهادات المصدرة</div>
            <div class="stat-value text-cyan-600">{{ $certificatesCount }}</div>
        </div>
    </div>

    @if($pendingCourses > 0)
    <div class="alert-warning">
        <i class="fas fa-hourglass-half text-amber-600"></i>
        <span>{{ $pendingCourses }} دورة قيد مراجعة الإدارة — ستُنشر بعد الموافقة.</span>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="flex gap-3 flex-wrap">
        <a href="{{ route('teacher.courses.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i> إضافة دورة جديدة
        </a>
        <a href="{{ route('teacher.withdraw-requests') }}" class="btn-success">
            <i class="fas fa-money-bill-wave"></i> طلب سحب الأرباح
        </a>
        <a href="{{ route('teacher.certificates') }}" class="btn" style="background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: #fff;">
            <i class="fas fa-certificate"></i> الشهادات
        </a>
    </div>

    <!-- Earnings Chart (last 6 months) -->
    @if(isset($earningsData) && $earningsData->count() > 0)
    <div class="card">
        <div class="card-header">
            <h3 class="font-bold flex items-center gap-2">
                <i class="fas fa-chart-line text-emerald-500"></i>
                الأرباح الشهرية (آخر 6 أشهر)
            </h3>
        </div>
        <div class="card-body">
            <div class="relative" style="height: 260px;">
                <canvas id="earningsChart"></canvas>
            </div>
        </div>
    </div>
    @endif

    <!-- Rating Summary + Recent Enrollments -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @if($avgRating)
        <div class="card">
            <div class="card-body flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-star text-3xl text-amber-400"></i>
                </div>
                <div>
                    <div class="text-3xl font-extrabold text-gray-800">{{ number_format($avgRating, 1) }}</div>
                    <div class="text-sm text-gray-500">متوسط التقييم</div>
                    <div class="flex gap-0.5 mt-1">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star text-xs {{ $i <= round($avgRating) ? 'text-amber-400' : 'text-gray-200' }}"></i>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($recentEnrollments->count())
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-user-graduate text-brand"></i>
                    آخر الاشتراكات
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table-dash">
                        <thead>
                            <tr>
                                <th>الطالب</th>
                                <th>الدورة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentEnrollments as $enrollment)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar bg-brand-100 text-brand text-xs">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <span class="font-medium text-gray-800">{{ $enrollment->student->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="text-gray-500">{{ \Str::limit($enrollment->course->title ?? '—', 50) }}</td>
                                <td class="text-gray-400">{{ $enrollment->created_at->format('Y-m-d') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
@if(isset($earningsData) && $earningsData->count() > 0)
<script>
    const earningsCtx = document.getElementById('earningsChart').getContext('2d');
    const earningsData = @json($earningsData);
    new Chart(earningsCtx, {
        type: 'bar',
        data: {
            labels: earningsData.map(d => d.month),
            datasets: [{
                label: 'الأرباح (د.ل)',
                data: earningsData.map(d => d.total),
                backgroundColor: '#823dbb',
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
</script>
@endif
@endpush
