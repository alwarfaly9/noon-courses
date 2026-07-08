@extends('layouts.admin')
@section('title', 'مستخدمو الشارة')
@section('content')
<div class="card">
    <div class="card-body">
        <div class="mb-6">
            <a href="{{ url('/admin/achievements') }}" class="text-brand hover:text-brand-light transition-colors text-sm font-medium">
                <i class="fas fa-arrow-right ml-1"></i> عودة للشارات
            </a>
            <h2 class="text-2xl font-bold mt-3 flex items-center gap-3">
                <i class="fas fa-trophy text-purple-600"></i>
                مستخدمو شارة: {{ $badge->name }}
            </h2>
        </div>
        <div class="table-container">
            <table class="table-dash">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>تاريخ الحصول</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $ub)
                    <tr>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="avatar bg-brand-100 text-brand text-xs">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="font-medium text-gray-800">{{ $ub->user->name ?? '—' }}</span>
                            </div>
                        </td>
                        <td class="text-gray-500">{{ $ub->earned_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center py-12">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <p class="empty-state-text">لا يوجد مستخدمون حصلوا على هذه الشارة</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $users->links() }}</div>
    </div>
</div>
@endsection
