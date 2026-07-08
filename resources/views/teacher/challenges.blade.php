@extends('layouts.teacher')
@section('title', 'التحديات')
@section('content')
<div class="card">
    <div class="card-header">
        <i class="fas fa-fire text-orange-500"></i>
        التحديات
        <a href="{{ route('teacher.challenges.create') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i> تحدي جديد
        </a>
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>العنوان</th>
                        <th>النوع</th>
                        <th>المشاركون</th>
                        <th>المدة</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($challenges as $c)
                    <tr>
                        <td class="font-medium">{{ \Str::limit($c->title, 40) }}</td>
                        <td class="text-sm">{{ $c->type }}</td>
                        <td>
                            <a href="{{ route('teacher.challenges.participants', $c) }}" class="text-purple-600 hover:underline">
                                {{ $c->participations_count ?? $c->participations()->count() }}
                            </a>
                        </td>
                        <td class="text-sm">
                            {{ $c->starts_at?->format('Y-m-d') }} → {{ $c->ends_at?->format('Y-m-d') }}
                        </td>
                        <td>
                            @if($c->is_active && $c->ends_at?->isFuture())
                            <span class="badge-success">نشط</span>
                            @elseif($c->ends_at?->isPast())
                            <span class="badge-info">منتهي</span>
                            @else
                            <span class="badge-neutral">معطل</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <a href="{{ route('teacher.challenges.edit', $c) }}" class="btn-warning btn-xs" title="تعديل"><i class="fas fa-edit"></i></a>
                                <a href="{{ route('teacher.challenges.participants', $c) }}" class="btn-primary btn-xs" title="المشاركون"><i class="fas fa-users"></i></a>
                                <form method="POST" action="{{ route('teacher.challenges.destroy', $c) }}" class="inline" onsubmit="return confirm('حذف التحدي؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger btn-xs" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="empty-state"><i class="empty-state-icon fas fa-fire"></i> لا توجد تحديات بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $challenges->links() }}</div>
    </div>
</div>
@endsection
