@extends('layouts.admin')
@section('title', 'الحملات')
@section('content')
<div class="card">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-bullhorn text-orange-500 mr-3"></i>
            إدارة الحملات
        </h2>
        <a href="{{ route('admin.campaigns.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i><span>حملة جديدة</span>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>المشاركون</th>
                    <th>المدة</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $c)
                <tr>
                    <td class="font-medium">{{ \Str::limit($c->name, 40) }}</td>
                    <td class="text-sm">{{ $c->type }}</td>
                    <td class="text-sm">{{ $c->participations_count }}</td>
                    <td class="text-sm">
                        {{ $c->starts_at?->format('Y-m-d') ?? '—' }} → {{ $c->ends_at?->format('Y-m-d') ?? '—' }}
                    </td>
                    <td>
                        @if($c->is_active)
                        <span class="badge-success">نشط</span>
                        @else
                        <span class="badge-danger">معطل</span>
                        @endif
                    </td>
                    <td class="text-sm space-x-2 space-x-reverse">
                        <a href="{{ route('admin.campaigns.edit', $c) }}" class="text-blue-600 hover:text-blue-900"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('admin.campaigns.destroy', $c) }}" class="inline" onsubmit="return confirm('حذف الحملة؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><i class="fas fa-bullhorn empty-state-icon"></i><p class="empty-state-text">لا توجد حملات</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $campaigns->links() }}</div>
</div>
@endsection
