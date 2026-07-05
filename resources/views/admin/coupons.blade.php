@extends('layouts.admin')

@section('title', 'الكوبونات')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-ticket-alt text-green-600 mr-3"></i>
            إدارة الكوبونات
        </h2>
        <button onclick="openAddModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center space-x-2 space-x-reverse btn-primary">
            <i class="fas fa-plus"></i>
            <span>إضافة كوبون</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الكود</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الاسم</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الخصم</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">المستخدمات</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">انتهاء الصلاحية</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($coupons as $coupon)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-mono text-sm font-bold text-green-600">{{ $coupon->code }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $coupon->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($coupon->discount_type === 'percentage')
                        <span class="text-green-600 font-semibold">{{ $coupon->discount_value }}%</span>
                        @else
                        <span class="text-green-600 font-semibold">{{ $coupon->discount_value }} د.ل</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                            {{ $coupon->used_count }} / {{ $coupon->usage_limit ?? '∞' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($coupon->is_active)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> نشط
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i> معطل
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 space-x-reverse">
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
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-ticket-alt text-4xl mb-2"></i>
                        <p>لا يوجد كوبونات</p>
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
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-8 w-96">
        <h3 class="text-2xl font-bold mb-6">إضافة كوبون جديد</h3>
        <form method="POST" action="{{ url('/admin/coupons') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الكود</label>
                <input type="text" name="code" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                <input type="text" name="name" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">نوع الخصم</label>
                <select name="discount_type" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
                    <option value="percentage">نسبة مئوية (%)</option>
                    <option value="fixed_amount">مبلغ ثابت (د.ل)</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">قيمة الخصم</label>
                <input type="number" name="discount_value" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">حد الاستخدام</label>
                <input type="number" name="usage_limit" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
            </div>
            <div class="flex space-x-4 space-x-reverse">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    إضافة
                </button>
                <button type="button" onclick="closeAddModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    إلغاء
                </button>
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

