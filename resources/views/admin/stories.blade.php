@extends('layouts.admin')
@section('title', 'القصص')
@section('content')
<div class="card">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-story text-purple-600 mr-3"></i>
            إدارة القصص
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>العنوان</th>
                    <th>المعلم</th>
                    <th>الدورة</th>
                    <th>المشاهدات</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stories as $story)
                <tr>
                    <td class="font-medium">{{ \Str::limit($story->title, 40) }}</td>
                    <td class="text-sm">{{ $story->user->name ?? '—' }}</td>
                    <td class="text-sm">{{ $story->course->title ?? 'عام' }}</td>
                    <td class="text-sm">{{ $story->views_count }}</td>
                    <td>
                        @if($story->is_active)
                        <span class="badge-success">نشط</span>
                        @else
                        <span class="badge-danger">معطل</span>
                        @endif
                    </td>
                    <td class="text-sm space-x-2 space-x-reverse">
                        <form method="POST" action="{{ url('/admin/stories/' . $story->id . '/toggle') }}" class="inline">
                            @csrf
                            <button class="text-{{ $story->is_active ? 'red' : 'green' }}-600 hover:underline">
                                {{ $story->is_active ? 'تعطيل' : 'تفعيل' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ url('/admin/stories/' . $story->id) }}" class="inline" onsubmit="return confirm('حذف القصة؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-900 mr-2"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><i class="fas fa-story empty-state-icon"></i><p class="empty-state-text">لا توجد قصص</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $stories->links() }}</div>
</div>
@endsection
