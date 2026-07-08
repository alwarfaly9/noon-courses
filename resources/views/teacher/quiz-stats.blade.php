@extends('layouts.teacher')
@section('title', 'إحصائيات الاختبار')
@section('content')
<div class="card">
    <div class="card-header">
        <i class="fas fa-chart-bar text-purple-600"></i>
        إحصائيات: {{ $quiz->title }}
        <a href="{{ route('teacher.quizzes.edit', $quiz) }}" class="btn-primary btn-sm">
            <i class="fas fa-edit"></i> تعديل
        </a>
    </div>
    <div class="card-body">
        {{-- Summary cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="stat-card-premium">
                <div class="text-sm text-gray-500">إجمالي المحاولات</div>
                <div class="text-3xl font-bold text-purple-700">{{ $totalAttempts }}</div>
            </div>
            <div class="stat-card-premium">
                <div class="text-sm text-gray-500">محاولات ناجحة</div>
                <div class="text-3xl font-bold text-purple-700">{{ $passedAttempts }}</div>
            </div>
            <div class="stat-card-premium">
                <div class="text-sm text-gray-500">متوسط الدرجات</div>
                <div class="text-3xl font-bold text-purple-700">{{ $averageScore ? number_format($averageScore, 1) . '%' : '—' }}</div>
            </div>
        </div>

        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الدرجة</th>
                        <th>النسبة</th>
                        <th>النتيجة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attempts as $attempt)
                    <tr>
                        <td class="font-medium">{{ $attempt->user->name ?? '—' }}</td>
                        <td>{{ $attempt->total_score }} / {{ $attempt->max_score }}</td>
                        <td>{{ $attempt->max_score > 0 ? number_format(($attempt->total_score / $attempt->max_score) * 100, 1) . '%' : '—' }}</td>
                        <td>
                            @if($attempt->passed)
                            <span class="badge-success">ناجح</span>
                            @else
                            <span class="badge-danger">راسب</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $attempt->completed_at->format('Y-m-d H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="empty-state"><i class="empty-state-icon fas fa-chart-bar"></i> لا توجد محاولات بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $attempts->links() }}</div>
    </div>
</div>
@endsection
