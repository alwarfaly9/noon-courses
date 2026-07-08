@extends('layouts.teacher')
@section('title', 'الاختبارات')
@section('content')
<div class="card">
    <div class="card-header">
        <i class="fas fa-question-circle text-purple-600"></i>
        اختبارات {{ $course->title }}
        <a href="{{ route('teacher.quizzes.create', $course) }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i> اختبار جديد
        </a>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>الدرجة المطلوبة</th>
                        <th>المدة</th>
                        <th>الأسئلة</th>
                        <th>محاولات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quizzes as $quiz)
                    <tr>
                        <td class="font-medium">{{ $quiz->title }}</td>
                        <td>{{ $quiz->pass_mark }}%</td>
                        <td>{{ $quiz->duration_minutes ? $quiz->duration_minutes . ' دقيقة' : '—' }}</td>
                        <td>{{ $quiz->questions_count }}</td>
                        <td>{{ $quiz->attempts_count }}</td>
                        <td>
                            <div class="flex gap-2">
                                <a href="{{ route('teacher.quizzes.edit', $quiz) }}" class="btn-warning btn-xs" title="تعديل"><i class="fas fa-edit"></i></a>
                                <a href="{{ route('teacher.quizzes.stats', $quiz) }}" class="btn-primary btn-xs" title="الإحصائيات"><i class="fas fa-chart-bar"></i></a>
                                <form method="POST" action="{{ route('teacher.quizzes.destroy', $quiz) }}" class="inline" onsubmit="return confirm('حذف الاختبار؟ جميع البيانات مرتبطة به')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger btn-xs" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="empty-state"><i class="empty-state-icon fas fa-question-circle"></i> لا توجد اختبارات لهذه الدورة بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
