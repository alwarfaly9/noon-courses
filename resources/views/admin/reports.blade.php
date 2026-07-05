@extends('layouts.admin')
@section('title', 'التقارير والإحصائيات')

@section('content')
<div class="space-y-6">

    {{-- Date range filter --}}
    <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between flex-wrap gap-3">
        <h2 class="text-xl font-bold flex items-center gap-2">
            <i class="fas fa-chart-bar text-green-600"></i> التقارير والإحصائيات
        </h2>
        <form method="GET" class="flex items-center gap-2">
            <label class="text-sm text-gray-600 font-medium">الفترة الزمنية:</label>
            <select name="months" onchange="this.form.submit()"
                    class="border rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-300">
                <option value="3"  {{ $months==3  ? 'selected':'' }}>آخر 3 أشهر</option>
                <option value="6"  {{ $months==6  ? 'selected':'' }}>آخر 6 أشهر</option>
                <option value="12" {{ $months==12 ? 'selected':'' }}>آخر 12 شهر</option>
                <option value="24" {{ $months==24 ? 'selected':'' }}>آخر 24 شهر</option>
            </select>
        </form>
    </div>

    {{-- KPI Cards --}}
    @php
        $kpiCards = [
            ['label'=>'إجمالي الإيرادات',  'value'=> number_format($kpis['total_revenue'],0).' د.ل',  'icon'=>'fa-money-bill-wave', 'bg'=>'bg-green-100',   'text'=>'text-green-700'],
            ['label'=>'أرباح المنصة',       'value'=> number_format($kpis['platform_earnings'],0).' د.ل','icon'=>'fa-landmark',         'bg'=>'bg-emerald-100', 'text'=>'text-emerald-700'],
            ['label'=>'اشتراكات جديدة',     'value'=> number_format($kpis['total_enrollments']),       'icon'=>'fa-user-plus',        'bg'=>'bg-blue-100',    'text'=>'text-blue-700'],
            ['label'=>'مستخدمون جدد',       'value'=> number_format($kpis['new_users']),               'icon'=>'fa-users',            'bg'=>'bg-indigo-100',  'text'=>'text-indigo-700'],
            ['label'=>'دورات جديدة',        'value'=> number_format($kpis['new_courses']),             'icon'=>'fa-graduation-cap',   'bg'=>'bg-purple-100',  'text'=>'text-purple-700'],
            ['label'=>'سحوبات معلقة',       'value'=> number_format($kpis['pending_withdraws'],0).' د.ل','icon'=>'fa-hourglass-half',  'bg'=>'bg-orange-100',  'text'=>'text-orange-700'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach($kpiCards as $card)
        <div class="bg-white rounded-xl shadow p-4 card">
            <div class="{{ $card['bg'] }} {{ $card['text'] }} rounded-full w-10 h-10 flex items-center justify-center mb-2">
                <i class="fas {{ $card['icon'] }}"></i>
            </div>
            <div class="text-lg font-bold leading-tight">{{ $card['value'] }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ $card['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- Revenue + Enrollments combo chart --}}
    <div class="bg-white rounded-xl shadow p-6 card">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-chart-line text-green-600"></i> الإيرادات والاشتراكات الشهرية
        </h3>
        <canvas id="revenueChart" height="100"></canvas>
    </div>

    {{-- Users growth + Category doughnut --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow p-6 card">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-users text-blue-600"></i> نمو المستخدمين
            </h3>
            <canvas id="usersChart" height="160"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow p-6 card">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-layer-group text-purple-600"></i> الاشتراكات حسب التصنيف
            </h3>
            <canvas id="categoryChart" height="160"></canvas>
        </div>
    </div>

    {{-- Top courses + Top teachers --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top Courses --}}
        <div class="bg-white rounded-xl shadow p-6 card">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-trophy text-yellow-500"></i> أكثر الدورات اشتراكاً
            </h3>
            <div class="space-y-2">
                @forelse($topCourses as $i => $course)
                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center
                        {{ $i===0 ? 'bg-yellow-400 text-white' : ($i===1 ? 'bg-gray-300 text-gray-700' : ($i===2 ? 'bg-orange-400 text-white' : 'bg-gray-100 text-gray-500')) }}">
                        {{ $i+1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ $course->title }}</div>
                        <div class="text-xs text-gray-400">
                            {{ $course->teacher->name ?? '—' }} · {{ $course->category->name ?? '—' }}
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-green-700 whitespace-nowrap">
                        {{ $course->students_count }} طالب
                    </span>
                </div>
                @empty
                <p class="text-gray-400 text-sm text-center py-4">لا توجد بيانات</p>
                @endforelse
            </div>
        </div>

        {{-- Top Teachers --}}
        <div class="bg-white rounded-xl shadow p-6 card">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <i class="fas fa-chalkboard-teacher text-purple-500"></i> أعلى المعلمين أرباحاً
            </h3>
            <div class="space-y-2">
                @forelse($topTeachers as $i => $teacher)
                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
                    <span class="flex-shrink-0 w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center
                        {{ $i===0 ? 'bg-yellow-400 text-white' : ($i===1 ? 'bg-gray-300 text-gray-700' : ($i===2 ? 'bg-orange-400 text-white' : 'bg-gray-100 text-gray-500')) }}">
                        {{ $i+1 }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ $teacher->name }}</div>
                        <div class="text-xs text-gray-400">{{ $teacher->published_courses_count }} دورة منشورة</div>
                    </div>
                    <span class="text-xs font-semibold text-purple-700 whitespace-nowrap">
                        {{ number_format($teacher->total_earnings ?? 0, 0) }} د.ل
                    </span>
                </div>
                @empty
                <p class="text-gray-400 text-sm text-center py-4">لا توجد بيانات</p>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const revenueRaw  = @json($revenueByMonth);
    const enrollRaw   = @json($enrollmentsByMonth);
    const usersRaw    = @json($usersByMonth);
    const categoryRaw = @json($enrollmentsByCategory);

    // Build unified month axis for combo chart
    const allMonths = [...new Set([
        ...revenueRaw.map(d => d.month),
        ...enrollRaw.map(d => d.month)
    ])].sort();

    function getVal(arr, month, key) {
        const row = arr.find(d => d.month === month);
        return row ? parseFloat(row[key]) : 0;
    }

    // Revenue + Enrollments combo (line + bar)
    new Chart(document.getElementById('revenueChart').getContext('2d'), {
        data: {
            labels: allMonths,
            datasets: [
                {
                    type: 'line',
                    label: 'الإيرادات (د.ل)',
                    data: allMonths.map(m => getVal(revenueRaw, m, 'total')),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'yRevenue',
                },
                {
                    type: 'bar',
                    label: 'الاشتراكات',
                    data: allMonths.map(m => getVal(enrollRaw, m, 'count')),
                    backgroundColor: 'rgba(59,130,246,0.6)',
                    yAxisID: 'yEnroll',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                yRevenue: { position: 'right', title: { display: true, text: 'الإيرادات (د.ل)' }, grid: { drawOnChartArea: false } },
                yEnroll:  { position: 'left',  title: { display: true, text: 'الاشتراكات' } }
            }
        }
    });

    // Users growth bar
    new Chart(document.getElementById('usersChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: usersRaw.map(d => d.month),
            datasets: [{
                label: 'مستخدمون جدد',
                data: usersRaw.map(d => d.count),
                backgroundColor: '#6366f1',
                borderRadius: 4,
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    // Enrollments by category doughnut
    new Chart(document.getElementById('categoryChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: categoryRaw.map(d => d.name),
            datasets: [{
                data: categoryRaw.map(d => d.count),
                backgroundColor: ['#10b981','#6366f1','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#14b8a6','#f97316'],
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
        }
    });
})();
</script>
@endpush

