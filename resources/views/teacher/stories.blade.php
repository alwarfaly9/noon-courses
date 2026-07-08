@extends('layouts.teacher')
@section('title', 'القصص')
@section('content')
<div class="card">
    <div class="card-header">
        <i class="fas fa-story text-purple-600"></i>
        قصصي
        <a href="{{ route('teacher.stories.create') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i> قصة جديدة
        </a>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>الدورة</th>
                        <th>المشاهدات</th>
                        <th>تاريخ النشر</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stories as $story)
                    <tr>
                        <td class="font-medium">{{ \Str::limit($story->title, 40) }}</td>
                        <td class="text-sm">{{ $story->course->title ?? 'عام' }}</td>
                        <td class="text-sm">{{ $story->views_count }}</td>
                        <td class="text-sm">{{ $story->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if($story->is_active)
                            <span class="badge-success">منشور</span>
                            @else
                            <span class="badge-neutral">مسودة</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <a href="{{ route('teacher.stories.edit', $story) }}" class="btn-warning btn-xs" title="تعديل"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('teacher.stories.destroy', $story) }}" class="inline" onsubmit="return confirm('حذف القصة؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger btn-xs" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="empty-state"><i class="empty-state-icon fas fa-story"></i> لا توجد قصص بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $stories->links() }}</div>
    </div>
</div>
@endsection
