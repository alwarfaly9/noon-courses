@extends('layouts.admin')

@section('title', 'المعاملات')

@section('content')
<div class="card card-body">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-exchange-alt"></i>
            المعاملات المالية
        </h2>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card-premium">
            <div class="flex items-center gap-3 mb-3">
                <div class="stat-icon-box bg-emerald-50 text-emerald-600">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <div class="stat-label">إجمالي الإيرادات</div>
            <div class="stat-value text-emerald-600">{{ number_format(\App\Models\Transaction::where('status', 'completed')->sum('amount'), 0) }} د.ل</div>
        </div>
        <div class="stat-card-premium">
            <div class="flex items-center gap-3 mb-3">
                <div class="stat-icon-box bg-blue-50 text-blue-600">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>
            <div class="stat-label">إجمالي المعاملات</div>
            <div class="stat-value text-blue-600">{{ \App\Models\Transaction::count() }}</div>
        </div>
        <div class="stat-card-premium">
            <div class="flex items-center gap-3 mb-3">
                <div class="stat-icon-box bg-amber-50 text-amber-600">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="stat-label">قيد المعالجة</div>
            <div class="stat-value text-amber-600">{{ \App\Models\Transaction::where('status', 'pending')->count() }}</div>
        </div>
        <div class="stat-card-premium">
            <div class="flex items-center gap-3 mb-3">
                <div class="stat-icon-box bg-purple-50 text-purple-600">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="stat-label">مكتملة</div>
            <div class="stat-value text-purple-600">{{ \App\Models\Transaction::where('status', 'completed')->count() }}</div>
        </div>
    </div>

    <div class="table-container">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>رقم العملية</th>
                    <th>المستخدم</th>
                    <th>نوع العملية</th>
                    <th>المبلغ</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                <tr>
                    <td class="font-mono">{{ $transaction->transaction_number }}</td>
                    <td>
                        <i class="fas fa-user ml-1"></i> {{ $transaction->user->name }}
                    </td>
                    <td>{{ $transaction->type }}</td>
                    <td class="font-semibold text-emerald-600">{{ $transaction->amount }} د.ل</td>
                    <td>
                        @if($transaction->status === 'completed')
                        <span class="badge-success">
                            <i class="fas fa-check-circle"></i> مكتملة
                        </span>
                        @else
                        <span class="badge-warning">
                            <i class="fas fa-clock"></i> {{ $transaction->status }}
                        </span>
                        @endif
                    </td>
                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="empty-state-title">لا توجد معاملات</div>
                            <div class="empty-state-text">لم يتم تسجيل أي معاملات مالية بعد</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
