@extends('layouts.admin')

@section('title', 'لوحة التحكم')

@section('content')
<div class="space-y-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="stat-card text-white p-6 rounded-lg shadow-lg card">
            <div class="flex items-center justify-between">
                <div>
                    @if(Auth::user()->hasRole('admin'))
                        <div class="text-sm opacity-90 mb-1"><i class="fas fa-users"></i> إجمالي المستخدمين</div>
                        <div class="text-3xl font-bold">{{ $stats['total_users'] }}</div>
                    @else
                        <div class="text-sm opacity-90 mb-1"><i class="fas fa-users"></i> إجمالي الطلاب</div>
                        <div class="text-3xl font-bold">{{ $stats['total_students'] ?? 0 }}</div>
                    @endif
                </div>
                <div class="text-5xl opacity-20">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-blue text-white p-6 rounded-lg shadow-lg card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-90 mb-1"><i class="fas fa-book"></i> {{ Auth::user()->hasRole('admin') ? 'الدورات المنشورة' : 'دوراتي المنشورة' }}</div>
                    <div class="text-3xl font-bold">{{ $stats['published_courses'] }}</div>
                </div>
                <div class="text-5xl opacity-20">
                    <i class="fas fa-book"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-orange text-white p-6 rounded-lg shadow-lg card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-90 mb-1"><i class="fas fa-clock"></i> {{ Auth::user()->hasRole('admin') ? 'الدورات قيد المراجعة' : 'دوراتي قيد المراجعة' }}</div>
                    <div class="text-3xl font-bold">{{ $stats['pending_courses'] }}</div>
                </div>
                <div class="text-5xl opacity-20">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card-purple text-white p-6 rounded-lg shadow-lg card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-90 mb-1"><i class="fas fa-money-bill-wave"></i> {{ Auth::user()->hasRole('admin') ? 'إجمالي الإيرادات' : 'إجمالي الأرباح' }}</div>
                    <div class="text-3xl font-bold">{{ number_format($stats[Auth::user()->hasRole('admin') ? 'total_revenue' : 'total_earnings'] ?? 0, 0) }} د.ل</div>
                </div>
                <div class="text-5xl opacity-20">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->hasRole('admin'))
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow-lg p-6 card">
            <h3 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-chart-line text-green-600 mr-2"></i>
                الإيرادات الشهرية
            </h3>
            <div class="relative h-72">
                <canvas id="revenueChart" class="w-full h-full"></canvas>
            </div>
        </div>

        <!-- User Growth Chart -->
        <div class="bg-white rounded-lg shadow-lg p-6 card">
            <h3 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-chart-area text-green-600 mr-2"></i>
                نمو المستخدمين
            </h3>
            <div class="relative h-72">
                <canvas id="userChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 {{ Auth::user()->hasRole('admin') ? 'lg:grid-cols-2' : '' }} gap-6">
        @if(Auth::user()->hasRole('admin'))
        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow-lg p-6 card">
            <h3 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-exchange-alt text-green-600 mr-2"></i>
                آخر المعاملات
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المستخدم</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($recentTransactions as $transaction)
                        <tr>
                            <td class="px-4 py-3 text-sm">{{ $transaction->user->name }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-green-600">{{ $transaction->amount }} د.ل</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    <i class="fas fa-{{ $transaction->status === 'completed' ? 'check' : 'clock' }} mr-1"></i>
                                    {{ $transaction->status }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="/admin/transactions" class="text-green-600 hover:text-green-800 font-semibold">
                    عرض جميع المعاملات <i class="fas fa-arrow-left mr-1"></i>
                </a>
            </div>
        </div>

        <!-- Pending Courses -->
        <div class="bg-white rounded-lg shadow-lg p-6 card">
            <h3 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-exclamation-triangle text-orange-600 mr-2"></i>
                الدورات قيد المراجعة
            </h3>
            <div class="space-y-3">
                @foreach($pendingCourses as $course)
                <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition">
                    <div class="font-semibold text-gray-800">{{ Str::limit($course->title, 40) }}</div>
                    <div class="text-sm text-gray-600 flex items-center mt-1">
                        <i class="fas fa-user mr-1"></i>
                        المحاضر: {{ $course->teacher->name }}
                    </div>
                    <div class="mt-2 flex space-x-2 space-x-reverse">
                        <form method="POST" action="/admin/courses/{{ $course->id }}/approve" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm flex items-center">
                                <i class="fas fa-check mr-1"></i> موافق
                            </button>
                        </form>
                        <form method="POST" action="/admin/courses/{{ $course->id }}/reject" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm flex items-center">
                                <i class="fas fa-times mr-1"></i> رفض
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-4">
                <a href="/admin/courses" class="text-green-600 hover:text-green-800 font-semibold">
                    عرض جميع الدورات <i class="fas fa-arrow-left mr-1"></i>
                </a>
            </div>
        </div>
        @else
        <!-- Recent Students (Teacher View) -->
        <div class="bg-white rounded-lg shadow-lg p-6 card">
            <h3 class="text-xl font-bold mb-4 flex items-center">
                <i class="fas fa-user-graduate text-green-600 mr-2"></i>
                آخر الطلاب المسجلين
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الطالب</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الدورة</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاريخ التسجيل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                         @forelse($recentEnrollments ?? [] as $enrollment)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $enrollment->student_name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->course_title }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($enrollment->created_at)->toDateString() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-500">لا يوجد تسجيلات حديثة</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    @if(auth()->user()->hasRole('admin'))
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = @json($revenueData);
    
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueData.map(d => d.month),
            datasets: [{
                label: 'الإيرادات (د.ل)',
                data: revenueData.map(d => d.total),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // User Growth Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    const userGrowthData = @json($userData);
    
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: userGrowthData.map(d => d.month),
            datasets: [{
                label: 'مستخدمين جدد',
                data: userGrowthData.map(d => d.count),
                backgroundColor: '#3b82f6',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    @endif
</script>
@endpush
@endsection
