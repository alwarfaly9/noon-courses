@extends('layouts.admin')

@section('title', 'طلبات السحب')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-money-bill-wave text-orange-600 mr-3"></i>
            طلبات السحب
        </h2>
    </div>

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center">
        <i class="fas fa-check-circle text-green-600 mr-2"></i>
        <span class="text-green-800">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
        <span class="text-red-800">{{ session('error') }}</span>
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">طلبات معلقة</div>
            <div class="text-2xl font-bold text-yellow-600">{{ $totalPending }}</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">إجمالي المدفوعات</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format($totalPaid, 0) }} د.ل</div>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">طلبات مرفوضة</div>
            <div class="text-2xl font-bold text-red-600">{{ $totalRejected }}</div>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="mb-4 flex gap-3 flex-wrap">
        <select name="status" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-300">
            <option value="">جميع الحالات</option>
            <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>معلق</option>
            <option value="paid"     {{ request('status') === 'paid'     ? 'selected' : '' }}>مدفوع</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوض</option>
        </select>
        <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-700">
            <i class="fas fa-filter mr-1"></i> تصفية
        </button>
        <a href="/admin/withdraw-requests" class="border px-4 py-2 rounded-lg text-sm hover:bg-gray-50">إعادة تعيين</a>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-orange-600 to-orange-700 text-white">
                <tr>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">#</th>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">المعلم</th>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">المبلغ</th>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">البنك</th>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">اسم الحساب</th>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">رقم الحساب</th>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">الحالة</th>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">التاريخ</th>
                    <th class="px-4 py-4 text-right text-sm font-semibold uppercase">إجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($requests as $wr)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4 text-sm text-gray-500">{{ $wr->id }}</td>
                    <td class="px-4 py-4 text-sm font-medium">
                        <div>{{ $wr->user->name }}</div>
                        <div class="text-xs text-gray-400">{{ $wr->user->email }}</div>
                    </td>
                    <td class="px-4 py-4 text-sm font-bold text-orange-600">{{ number_format($wr->amount, 2) }} د.ل</td>
                    <td class="px-4 py-4 text-sm">{{ $wr->bank_name }}</td>
                    <td class="px-4 py-4 text-sm">{{ $wr->account_name }}</td>
                    <td class="px-4 py-4 text-sm font-mono">{{ $wr->account_number }}</td>
                    <td class="px-4 py-4 text-sm">
                        @if($wr->status === 'pending')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i> معلق
                        </span>
                        @elseif($wr->status === 'paid')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> مدفوع
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800"
                              title="{{ $wr->rejection_reason }}">
                            <i class="fas fa-times-circle mr-1"></i> مرفوض
                        </span>
                        @endif
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-500">{{ $wr->created_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-4 text-sm">
                        @if($wr->status === 'pending')
                        <div class="flex gap-2">
                            {{-- Approve --}}
                            <form method="POST" action="/admin/withdraw-requests/{{ $wr->id }}/approve"
                                  onsubmit="return confirm('تأكيد الموافقة على هذا الطلب؟')">
                                @csrf
                                <button type="submit"
                                        class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1.5 rounded-lg">
                                    <i class="fas fa-check mr-1"></i> موافقة
                                </button>
                            </form>

                            {{-- Reject --}}
                            <button type="button"
                                    onclick="openRejectModal({{ $wr->id }})"
                                    class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded-lg">
                                <i class="fas fa-times mr-1"></i> رفض
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
                    <td colspan="9" class="px-6 py-10 text-center text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2 block"></i>
                        لا توجد طلبات سحب
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
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-2xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold mb-4 flex items-center">
            <i class="fas fa-times-circle text-red-600 mr-2"></i>
            رفض طلب السحب
        </h3>
        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">سبب الرفض <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" required
                          class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-300"
                          placeholder="اذكر سبب رفض الطلب..."></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">إلغاء</button>
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                    <i class="fas fa-times mr-1"></i> تأكيد الرفض
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
