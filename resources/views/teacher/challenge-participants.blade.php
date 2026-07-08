@extends('layouts.teacher')
@section('title', 'مشاركو التحدي')
@section('content')
<div class="card">
    <div class="card-header">
        <a href="{{ route('teacher.challenges.index') }}" class="text-purple-600 hover:underline ml-3">
            <i class="fas fa-arrow-right ml-1"></i> عودة للتحديات
        </a>
        <i class="fas fa-users text-orange-500"></i>
        مشاركو: {{ $challenge->title }}
    </div>
    <div class="card-body">
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>تاريخ الانضمام</th>
                        <th>التقدم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $p)
                    <tr>
                        <td>{{ $p->user->name ?? '—' }}</td>
                        <td class="text-sm">{{ $p->created_at->format('Y-m-d') }}</td>
                        <td class="text-sm">{{ $p->progress ?? 0 }} / {{ $challenge->goal_value }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty-state"><i class="empty-state-icon fas fa-users"></i> لا يوجد مشاركون بعد</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $participants->links() }}</div>
    </div>
</div>
@endsection
