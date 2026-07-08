@extends('layouts.admin')

@section('title', 'الدعم الفني')

@section('content')
<div class="card card-body">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-headset"></i>
            إدارة تذاكر الدعم
        </h2>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card-premium">
            <div class="flex items-center gap-3 mb-3">
                <div class="stat-icon-box bg-red-50 text-red-600">
                    <i class="fas fa-circle"></i>
                </div>
            </div>
            <div class="stat-label">مفتوحة</div>
            <div class="stat-value text-red-600">{{ \App\Models\SupportTicket::where('status', 'open')->count() }}</div>
        </div>
        <div class="stat-card-premium">
            <div class="flex items-center gap-3 mb-3">
                <div class="stat-icon-box bg-amber-50 text-amber-600">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-label">قيد المعالجة</div>
            <div class="stat-value text-amber-600">{{ \App\Models\SupportTicket::where('status', 'in_progress')->count() }}</div>
        </div>
        <div class="stat-card-premium">
            <div class="flex items-center gap-3 mb-3">
                <div class="stat-icon-box bg-emerald-50 text-emerald-600">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <div class="stat-label">مُحلّة</div>
            <div class="stat-value text-emerald-600">{{ \App\Models\SupportTicket::where('status', 'resolved')->count() }}</div>
        </div>
        <div class="stat-card-premium">
            <div class="flex items-center gap-3 mb-3">
                <div class="stat-icon-box bg-blue-50 text-blue-600">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            </div>
            <div class="stat-label">إجمالي التذاكر</div>
            <div class="stat-value text-blue-600">{{ \App\Models\SupportTicket::count() }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <select name="status" class="form-select w-44">
                <option value="">جميع الحالات</option>
                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>مفتوحة</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>قيد المعالجة</option>
                <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>محلّة</option>
            </select>
            <button type="submit" class="btn-primary">
                <i class="fas fa-filter"></i> تصفية
            </button>
        </form>
    </div>

    <div class="table-container">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>رقم التذكرة</th>
                    <th>المستخدم</th>
                    <th>الموضوع</th>
                    <th>الأولوية</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                <tr>
                    <td>
                        <div class="font-mono text-sm font-bold text-brand">{{ $ticket->ticket_number }}</div>
                    </td>
                    <td>
                        <i class="fas fa-user ml-1"></i> {{ $ticket->user->name }}
                    </td>
                    <td>{{ Str::limit($ticket->subject, 40) }}</td>
                    <td>
                        @if($ticket->priority === 'urgent')
                        <span class="badge-danger">
                            <i class="fas fa-exclamation-circle"></i> عاجل
                        </span>
                        @elseif($ticket->priority === 'high')
                        <span class="badge-orange">
                            <i class="fas fa-arrow-up"></i> عالية
                        </span>
                        @elseif($ticket->priority === 'medium')
                        <span class="badge-warning">
                            <i class="fas fa-minus"></i> متوسطة
                        </span>
                        @else
                        <span class="badge-neutral">
                            <i class="fas fa-arrow-down"></i> منخفضة
                        </span>
                        @endif
                    </td>
                    <td>
                        @if($ticket->status === 'open')
                        <span class="badge-danger">
                            <i class="fas fa-circle"></i> مفتوحة
                        </span>
                        @elseif($ticket->status === 'in_progress')
                        <span class="badge-warning">
                            <i class="fas fa-clock"></i> قيد المعالجة
                        </span>
                        @elseif($ticket->status === 'resolved')
                        <span class="badge-success">
                            <i class="fas fa-check"></i> محلّة
                        </span>
                        @else
                        <span class="badge-neutral">
                            <i class="fas fa-times"></i> مغلقة
                        </span>
                        @endif
                    </td>
                    <td>
                        {{ $ticket->created_at->format('Y-m-d') }}
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <button class="text-blue-600 hover:text-blue-900" title="عرض">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if($ticket->status === 'open')
                            <button class="text-emerald-600 hover:text-emerald-900" title="تعيين">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="empty-state-title">لا توجد تذاكر</div>
                            <div class="empty-state-text">لم يتم تقديم أي تذاكر دعم بعد</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $tickets->links() }}
    </div>
</div>
@endsection
