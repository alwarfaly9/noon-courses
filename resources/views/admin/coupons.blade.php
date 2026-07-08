@extends('layouts.admin')

@section('title', 'الكوبونات')

@section('content')
<div class="card">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-ticket-alt text-green-600 mr-3"></i>
            إدارة الكوبونات
        </h2>
        <button onclick="openAddModal()" class="btn-primary">
            <i class="fas fa-plus"></i>
            <span>إضافة كوبون</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>الاسم</th>
                    <th>الخصم</th>
                    <th>المستخدمات</th>
                    <th>الحالة</th>
                    <th>انتهاء الصلاحية</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $coupon)
                <tr>
                    <td>
                        <div class="font-mono text-sm font-bold text-green-600">{{ $coupon->code }}</div>
                    </td>
                    <td><span class="text-sm font-medium text-gray-900">{{ $coupon->name }}</span></td>
                    <td>
                        @if($coupon->discount_type === 'percentage')
                        <span class="text-green-600 font-semibold">{{ $coupon->discount_value }}%</span>
                        @else
                        <span class="text-green-600 font-semibold">{{ $coupon->discount_value }} د.ل</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge-info">
                            {{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}
                        </span>
                    </td>
                    <td>
                        @if($coupon->is_active)
                        <span class="badge-success">
                            <i class="fas fa-check-circle ml-1"></i> نشط
                        </span>
                        @else
                        <span class="badge-danger">
                            <i class="fas fa-times-circle ml-1"></i> معطل
                        </span>
                        @endif
                    </td>
                    <td><span class="text-sm text-gray-500">{{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '-' }}</span></td>
                    <td class="space-x-2 space-x-reverse">
                        <button class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-ticket-alt empty-state-icon"></i>
                            <p class="empty-state-text">لا يوجد كوبونات</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $coupons->links() }}
    </div>
</div>

<!-- Add Coupon Modal -->
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>إضافة كوبون جديد</h3>
        </div>
        <form method="POST" action="{{ url('/admin/coupons') }}">
            @csrf
            <div class="modal-body">
                <div class="mb-4">
                    <label class="form-label">الكود</label>
                    <input type="text" name="code" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">الاسم</label>
                    <input type="text" name="name" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">نوع الخصم</label>
                    <select name="discount_type" required class="form-select">
                        <option value="percentage">نسبة مئوية (%)</option>
                        <option value="fixed_amount">مبلغ ثابت (د.ل)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">قيمة الخصم</label>
                    <input type="number" name="discount_value" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">حد الاستخدام</label>
                    <input type="number" name="usage_limit" class="form-input">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-primary">إضافة</button>
                <button type="button" onclick="closeAddModal()" class="btn-neutral">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}
</script>
@endsection
