@extends('layouts.teacher')
@section('title', 'دوراتي')
@section('content')
<div class="card card-body p-0">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-graduation-cap text-purple-600"></i> دوراتي
        </h2>
        <a href="{{ route('teacher.courses.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i> إضافة دورة
        </a>
    </div>

    <div class="table-container">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>الدورة</th>
                    <th>التصنيف</th>
                    <th>السعر</th>
                    <th>الطلاب</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            @if($course->image)
                            <img src="{{ asset('storage/'.$course->image) }}"
                                 class="w-12 h-12 rounded-lg object-cover" alt="">
                            @else
                            <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-book text-purple-400"></i>
                            </div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900">{{ \Str::limit($course->title, 50) }}</div>
                                <div class="text-xs text-gray-400">{{ $course->level }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $course->category->name ?? '—' }}</td>
                    <td class="font-semibold text-purple-600">
                        {{ $course->price > 0 ? $course->price.' د.ل' : 'مجاني' }}
                    </td>
                    <td class="text-center">
                        <span class="badge-info">
                            {{ $course->students_count ?? 0 }}
                        </span>
                    </td>
                    <td>
                        @if($course->status === 'published')
                        <span class="badge-success">
                            <i class="fas fa-check-circle ml-1"></i> منشور
                        </span>
                        @elseif($course->status === 'pending')
                        <span class="badge-warning">
                            <i class="fas fa-hourglass-half ml-1"></i> قيد المراجعة
                        </span>
                        @elseif($course->status === 'rejected')
                        <span class="badge-danger">
                            <i class="fas fa-times-circle ml-1"></i> مرفوض
                        </span>
                        @else
                        <span class="badge-neutral">{{ $course->status }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-2 flex-wrap">
                            <a href="{{ route('teacher.courses.content', $course->id) }}"
                               class="btn-primary btn-xs">
                                <i class="fas fa-list"></i> المحتوى
                            </a>
                            <a href="{{ route('teacher.courses.edit', $course->id) }}"
                               class="btn-warning btn-xs">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <form method="POST" action="{{ route('teacher.courses.destroy', $course->id) }}"
                                  onsubmit="return confirm('هل أنت متأكد من حذف الدورة؟')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-xs">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        <i class="empty-state-icon fas fa-inbox"></i>
                        لا توجد دورات —
                        <a href="{{ route('teacher.courses.create') }}">أضف أولى دوراتك</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $courses->links() }}</div>
</div>
@endsection
