@extends('layouts.teacher')

@section('title', 'لوحة التحليلات')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-chart-pie text-purple-600"></i> لوحة التحليلات
        </h2>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-brand-50 flex items-center justify-center">
                    <i class="fas fa-book text-brand"></i>
                </div>
            </div>
            <div class="stat-label">الدورات</div>
            <div class="stat-value text-brand">{{ $stats['courses']['total'] }}</div>
        </div>
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                    <i class="fas fa-users text-blue-500"></i>
                </div>
            </div>
            <div class="stat-label">الطلاب</div>
            <div class="stat-value text-blue-600">{{ $stats['students']['total'] }}</div>
        </div>
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <i class="fas fa-check-circle text-emerald-500"></i>
                </div>
            </div>
            <div class="stat-label">نسبة الإكمال</div>
            <div class="stat-value text-emerald-600">{{ $stats['students']['completion_rate'] }}%</div>
        </div>
        <div class="stat-card-mini">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-orange-500"></i>
                </div>
            </div>
            <div class="stat-label">الإيرادات (هذا الشهر)</div>
            <div class="stat-value text-orange-600">{{ number_format($stats['revenue']['this_month']) }} ل.د</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-user-graduate text-brand"></i>
                    أداء الطلاب
                </h3>
            </div>
            <div class="card-body">
                <div class="relative" style="height: 240px;">
                    <canvas id="studentsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="font-bold flex items-center gap-2">
                    <i class="fas fa-question-circle text-amber-500"></i>
                    نتائج الاختبارات
                </h3>
            </div>
            <div class="card-body">
                <div class="relative" style="height: 240px;">
                    <canvas id="quizChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="font-bold flex items-center gap-2">
                <i class="fas fa-list text-brand"></i>
                ملخص سريع
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table-dash">
                    <thead>
                        <tr>
                            <th>المؤشر</th>
                            <th>القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="font-medium text-gray-700">إجمالي التسجيلات</td>
                            <td>{{ $stats['students']['enrollments'] }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">الطلاب المكملون</td>
                            <td>{{ $stats['students']['completions'] }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">إجمالي الإيرادات</td>
                            <td class="font-semibold text-emerald-600">{{ number_format($stats['revenue']['total']) }} ل.د</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">محاولات الاختبارات</td>
                            <td>{{ $stats['quizzes']['total_attempts'] }}</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-700">نسبة النجاح في الاختبارات</td>
                            <td>
                                <span class="badge-{{ $stats['quizzes']['pass_rate'] >= 60 ? 'success' : 'warning' }}">
                                    {{ $stats['quizzes']['pass_rate'] }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('studentsChart'), {
    type: 'doughnut',
    data: {
        labels: ['مكملون', 'قيد التقدم'],
        datasets: [{
            data: [{{ $stats['students']['completions'] }}, {{ max(0, $stats['students']['enrollments'] - $stats['students']['completions']) }}],
            backgroundColor: ['#10b981', '#c59bd7'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        animation: {
            duration: 800,
            easing: 'easeOutQuart'
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { family: 'Tajawal', size: 11 },
                    padding: 16,
                    usePointStyle: true,
                }
            },
            tooltip: {
                backgroundColor: '#1e1e2a',
                titleColor: '#fff',
                bodyColor: '#e2e8f0',
                cornerRadius: 10,
                padding: 12,
            }
        }
    }
});

new Chart(document.getElementById('quizChart'), {
    type: 'doughnut',
    data: {
        labels: ['ناجح', 'راسب'],
        datasets: [{
            data: [{{ $stats['quizzes']['passed'] }}, {{ max(0, $stats['quizzes']['total_attempts'] - $stats['quizzes']['passed']) }}],
            backgroundColor: ['#10b981', '#ef4444'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        animation: {
            duration: 800,
            easing: 'easeOutQuart'
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    font: { family: 'Tajawal', size: 11 },
                    padding: 16,
                    usePointStyle: true,
                }
            },
            tooltip: {
                backgroundColor: '#1e1e2a',
                titleColor: '#fff',
                bodyColor: '#e2e8f0',
                cornerRadius: 10,
                padding: 12,
            }
        }
    }
});
</script>
@endpush
