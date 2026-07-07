@extends('layouts.teacher')
@section('title', 'لوحة التحكم')
@section('content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">
        مرحباً، {{ Auth::user()->name }}
    </h2>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-purple-500">
            <div class="text-sm text-gray-500 mb-1">إجمالي الدورات</div>
            <div class="text-3xl font-bold text-purple-600">{{ $totalCourses }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-green-500">
            <div class="text-sm text-gray-500 mb-1">دورات منشورة</div>
            <div class="text-3xl font-bold text-green-600">{{ $publishedCourses }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-blue-500">
            <div class="text-sm text-gray-500 mb-1">إجمالي الطلاب</div>
            <div class="text-3xl font-bold text-blue-600">{{ $totalStudents }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-orange-500">
            <div class="text-sm text-gray-500 mb-1">أرباحك الكلية</div>
            <div class="text-3xl font-bold text-orange-600">{{ number_format($totalEarnings, 0) }} د.ل</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-cyan-500">
            <div class="text-sm text-gray-500 mb-1">الشهادات المصدرة</div>
            <div class="text-3xl font-bold text-cyan-600">{{ $certificatesCount }}</div>
        </div>
    </div>

    @if($pendingCourses > 0)
    <div class="bg-yellow-50 border border-yellow-300 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-hourglass-half text-yellow-500 text-xl"></i>
        <span class="text-yellow-800">{{ $pendingCourses }} دورة قيد مراجعة الإدارة — ستُنشر بعد الموافقة.</span>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="flex gap-3 flex-wrap">
        <a href="{{ route('teacher.courses.create') }}"
           class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2">
            <i class="fas fa-plus"></i> إضافة دورة جديدة
        </a>
        <a href="{{ route('teacher.withdraw-requests') }}"
           class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2">
            <i class="fas fa-money-bill-wave"></i> طلب سحب الأرباح
        </a>
        <a href="{{ route('teacher.certificates') }}"
           class="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2">
            <i class="fas fa-certificate"></i> الشهادات
        </a>
    </div>

    <!-- Earnings Chart (last 6 months) -->
    @if($earningsData->count() > 0)
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="text-lg font-bold mb-4 text-gray-700">
            <i class="fas fa-chart-line text-green-500 ml-2"></i>
            الأرباح الشهرية (آخر 6 أشهر)
        </h3>
        <div class="relative h-64">
            <canvas id="earningsChart" class="w-full h-full"></canvas>
        </div>
    </div>
    @endif

    <!-- Rating Summary -->
    @if($avgRating)
    <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
        <div class="text-4xl text-yellow-500">
            <i class="fas fa-star"></i>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($avgRating, 1) }} / 5</div>
            <div class="text-sm text-gray-500">متوسط تقييم دوراتك</div>
        </div>
    </div>
    @endif

    <!-- Recent Enrollments -->
    @if($recentEnrollments->count())
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="text-lg font-bold mb-4 text-gray-700">
            <i class="fas fa-user-graduate text-purple-500 ml-2"></i>
            آخر الاشتراكات
        </h3>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">الطالب</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">الدورة</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($recentEnrollments as $enrollment)
                <tr>
                    <td class="px-4 py-3">{{ $enrollment->student->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ \Str::limit($enrollment->course->title ?? '—', 50) }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $enrollment->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

@push('scripts')
@if($earningsData->count() > 0)
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
                backgroundColor: '#10b981',
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' }
                }
            }
        }
    });
</script>
@endif
@endpush
