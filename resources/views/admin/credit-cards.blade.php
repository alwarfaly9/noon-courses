@extends('layouts.admin')

@section('title', 'كروت الرصيد')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-credit-card text-green-600 mr-3"></i>
            إدارة كروت الرصيد
        </h2>
        <button onclick="openGenerateModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center space-x-2 space-x-reverse btn-primary">
            <i class="fas fa-plus"></i>
            <span>توليد كروت جديدة</span>
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">فعالة</div>
            <div class="text-2xl font-bold text-green-600">{{ \App\Models\CreditCard::where('status', 'active')->count() }}</div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">مستخدمة</div>
            <div class="text-2xl font-bold text-blue-600">{{ \App\Models\CreditCard::where('status', 'used')->count() }}</div>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">منتهية</div>
            <div class="text-2xl font-bold text-gray-600">{{ \App\Models\CreditCard::where('status', 'expired')->count() }}</div>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">إجمالي الكروت</div>
            <div class="text-2xl font-bold text-purple-600">{{ \App\Models\CreditCard::count() }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <select name="status" class="px-4 py-2 border border-gray-300 rounded">
                <option value="">جميع الحالات</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعالة</option>
                <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>مستخدمة</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>منتهية</option>
            </select>
            <select name="value" class="px-4 py-2 border border-gray-300 rounded">
                <option value="">جميع القيم</option>
                <option value="10" {{ request('value') == '10' ? 'selected' : '' }}>10 د.ل</option>
                <option value="25" {{ request('value') == '25' ? 'selected' : '' }}>25 د.ل</option>
                <option value="50" {{ request('value') == '50' ? 'selected' : '' }}>50 د.ل</option>
                <option value="100" {{ request('value') == '100' ? 'selected' : '' }}>100 د.ل</option>
                <option value="250" {{ request('value') == '250' ? 'selected' : '' }}>250 د.ل</option>
            </select>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">
                <i class="fas fa-filter"></i> تصفية
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">رقم السريال</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">القيمة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">مستخدم من قبل</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">تاريخ الاستخدام</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($creditCards as $card)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-mono text-sm font-bold text-gray-900">{{ $card->serial_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                        <i class="fas fa-money-bill-wave mr-1"></i> {{ $card->value }} د.ل
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($card->status === 'active')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> فعالة
                        </span>
                        @elseif($card->status === 'used')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            <i class="fas fa-check mr-1"></i> مستخدمة
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            <i class="fas fa-times mr-1"></i> منتهية
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($card->used_by)
                        <i class="fas fa-user mr-1"></i> {{ $card->user->name ?? 'غير متاح' }}
                        @else
                        <span class="text-gray-400">لم يتم الاستخدام</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $card->used_at ? $card->used_at->format('Y-m-d') : '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-credit-card text-4xl mb-2"></i>
                        <p>لا يوجد كروت</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $creditCards->links() }}
    </div>
</div>

<!-- Generate Modal -->
<div id="generateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-8 w-96">
        <h3 class="text-2xl font-bold mb-6">توليد كروت جديدة</h3>
        <form method="POST" action="{{ url('/admin/credit-cards/generate') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">العدد</label>
                <input type="number" name="count" min="1" max="1000" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">القيمة (د.ل)</label>
                <select name="value" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight">
                    <option value="">اختر القيمة</option>
                    <option value="10">10 د.ل</option>
                    <option value="25">25 د.ل</option>
                    <option value="50">50 د.ل</option>
                    <option value="100">100 د.ل</option>
                    <option value="250">250 د.ل</option>
                </select>
            </div>
            <div class="flex space-x-4 space-x-reverse">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    توليد
                </button>
                <button type="button" onclick="closeGenerateModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openGenerateModal() {
    document.getElementById('generateModal').classList.remove('hidden');
}

function closeGenerateModal() {
    document.getElementById('generateModal').classList.add('hidden');
}
</script>
@endsection

