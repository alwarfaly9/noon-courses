@extends('layouts.admin')

@section('title', 'طلبات السحب')

@section('content')
<div class="card">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-money-bill-wave text-orange-600 mr-3"></i>
            طلبات السحب
        </h2>
    </div>

    @if(session('success'))
    <div class="alert-success">
        <i class="fas fa-check-circle ml-2"></i>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="alert-danger">
        <i class="fas fa-exclamation-circle ml-2"></i>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="stat-card-premium bg-yellow-50 border-yellow-200">
            <div class="text-sm text-gray-600 mb-1">طلبات معلقة</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $totalPending }}</div>
        </div>
        <div class="stat-card-premium bg-green-50 border-green-200">
            <div class="text-sm text-gray-600 mb-1">إجمالي المدفوعات</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($totalPaid, 0) }} د.ل</div>
        </div>
        <div class="stat-card-premium bg-red-50 border-red-200">
            <div class="text-sm text-gray-600 mb-1">طلبات مرفوضة</div>
            <div class="text-2xl font-bold text-red-600">{{ $totalRejected }}</div>
        </div>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
        <form method="GET" class="flex gap-3 flex-wrap">
            <select name="status" class="form-select w-44">
                <option value="">جميع الحالات</option>
                <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>معلق</option>
                <option value="paid"     {{ request('status') === 'paid'     ? 'selected' : '' }}>مدفوع</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
            </select>
            <button type="submit" class="btn-warning">
                <i class="fas fa-filter ml-1"></i> تصفية
            </button>
            <a href="/admin/withdraw-requests" class="btn-neutral">إعادة تعيين</a>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المعلم</th>
                    <th>المبلغ</th>
                    <th>البنك</th>
                    <th>اسم الحساب</th>
                    <th>رقم الحساب</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $wr)
                <tr>
                    <td class="text-gray-500">{{ $wr->id }}</td>
                    <td>
                        <div class="font-medium">{{ $wr->user->name }}</div>
                        <div class="text-xs text-gray-400">{{ $wr->user->email }}</div>
                    </td>
                    <td class="font-bold text-orange-600">{{ number_format($wr->amount, 2) }} د.ل</td>
                    <td class="text-sm">{{ $wr->bank_name }}</td>
                    <td class="text-sm">{{ $wr->account_name }}</td>
                    <td class="text-sm font-mono">{{ $wr->account_number }}</td>
                    <td>
                        @if($wr->status === 'pending')
                        <span class="badge-warning">
                            <i class="fas fa-clock ml-1"></i> معلق
                        </span>
                        @elseif($wr->status === 'paid')
                        <span class="badge-success">
                            <i class="fas fa-check-circle ml-1"></i> مدفوع
                        </span>
                        @else
                        <span class="badge-danger" title="{{ $wr->rejection_reason }}">
                            <i class="fas fa-times-circle ml-1"></i> مرفوض
                        </span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500">{{ $wr->created_at->format('Y-m-d H:i') }}</td>
                    <td>
                        @if($wr->status === 'pending')
                        <div class="flex gap-2">
                            {{-- Approve --}}
                            <form method="POST" action="/admin/withdraw-requests/{{ $wr->id }}/approve"
                                  onsubmit="return confirm('تأكيد الموافقة على هذا الطلب؟')">
                                @csrf
                                <button type="submit" class="btn-success btn-xs">
                                    <i class="fas fa-check ml-1"></i> موافقة
                                </button>
                            </form>

                            {{-- Reject --}}
                            <button type="button"
                                    onclick="openRejectModal({{ $wr->id }})"
                                    class="btn-danger btn-xs">
                                <i class="fas fa-times ml-1"></i> رفض
                            </button>
                        </div>
                        @elseif($wr->status === 'paid')
                        <span class="text-xs text-gray-400">
                            معتمد بواسطة {{ $wr->processor?->name ?? '—' }}<br>
                            {{ $wr->processed_at?->format('Y-m-d') }}
                        </span>
                        @else
                        <span class="text-xs text-gray-400" title="{{ $wr->rejection_reason }}">
                            {{ Str::limit($wr->rejection_reason, 40) }}
                        </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="fas fa-inbox empty-state-icon"></i>
                            <p class="empty-state-text">لا توجد طلبات سحب</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $requests->withQueryString()->links() }}
    </div>
</div>

{{-- Reject Modal --}}
<div id="rejectModal" class="modal-overlay hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h3><i class="fas fa-times-circle text-red-600 ml-2"></i> رفض طلب السحب</h3>
        </div>
        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="modal-body">
                <div class="mb-4">
                    <label class="form-label">سبب الرفض <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required class="form-textarea" placeholder="اذكر سبب رفض الطلب..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeRejectModal()" class="btn-neutral">إلغاء</button>
                <button type="submit" class="btn-danger">
                    <i class="fas fa-times ml-1"></i> تأكيد الرفض
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(id) {
    document.getElementById('rejectForm').action = '/admin/withdraw-requests/' + id + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>
@endsection
