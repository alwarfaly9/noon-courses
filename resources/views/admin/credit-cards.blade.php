@extends('layouts.admin')

@section('title', 'كروت الرصيد')

@section('content')
<div class="card">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-credit-card text-green-600 mr-3"></i>
            إدارة كروت الرصيد
        </h2>
        <button onclick="openGenerateModal()" class="btn-primary">
            <i class="fas fa-plus"></i>
            <span>توليد كروت جديدة</span>
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-card-premium bg-green-50 border-green-200">
            <div class="text-sm text-gray-600 mb-1">فعالة</div>
            <div class="text-2xl font-bold text-green-600">{{ \App\Models\CreditCard::where('status', 'active')->count() }}</div>
        </div>
        <div class="stat-card-premium bg-blue-50 border-blue-200">
            <div class="text-sm text-gray-600 mb-1">مستخدمة</div>
            <div class="text-2xl font-bold text-blue-600">{{ \App\Models\CreditCard::where('status', 'used')->count() }}</div>
        </div>
        <div class="stat-card-premium bg-gray-50 border-gray-200">
            <div class="text-sm text-gray-600 mb-1">منتهية</div>
            <div class="text-2xl font-bold text-gray-600">{{ \App\Models\CreditCard::where('status', 'expired')->count() }}</div>
        </div>
        <div class="stat-card-premium bg-purple-50 border-purple-200">
            <div class="text-sm text-gray-600 mb-1">إجمالي الكروت</div>
            <div class="text-2xl font-bold text-purple-600">{{ \App\Models\CreditCard::count() }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <select name="status" class="filter-select">
                <option value="">جميع الحالات</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>فعالة</option>
                <option value="used" {{ request('status') == 'used' ? 'selected' : '' }}>مستخدمة</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>منتهية</option>
            </select>
            <select name="value" class="filter-select">
                <option value="">جميع القيم</option>
                <option value="10" {{ request('value') == '10' ? 'selected' : '' }}>10 د.ل</option>
                <option value="25" {{ request('value') == '25' ? 'selected' : '' }}>25 د.ل</option>
                <option value="50" {{ request('value') == '50' ? 'selected' : '' }}>50 د.ل</option>
                <option value="100" {{ request('value') == '100' ? 'selected' : '' }}>100 د.ل</option>
                <option value="250" {{ request('value') == '250' ? 'selected' : '' }}>250 د.ل</option>
            </select>
            <button type="submit" class="btn-primary">
                <i class="fas fa-filter"></i> تصفية
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>رقم السريال</th>
                    <th>القيمة</th>
                    <th>الحالة</th>
                    <th>مستخدم من قبل</th>
                    <th>تاريخ الاستخدام</th>
                </tr>
            </thead>
            <tbody>
                @forelse($creditCards as $card)
                <tr>
                    <td>
                        <div class="font-mono text-sm font-bold text-gray-900">{{ $card->serial_number }}</div>
                    </td>
                    <td class="font-semibold text-green-600">
                        <i class="fas fa-money-bill-wave ml-1"></i> {{ $card->value }} د.ل
                    </td>
                    <td>
                        @if($card->status === 'active')
                        <span class="badge-success">
                            <i class="fas fa-check-circle ml-1"></i> فعالة
                        </span>
                        @elseif($card->status === 'used')
                        <span class="badge-info">
                            <i class="fas fa-check ml-1"></i> مستخدمة
                        </span>
                        @else
                        <span class="badge-neutral">
                            <i class="fas fa-times ml-1"></i> منتهية
                        </span>
                        @endif
                    </td>
                    <td class="text-sm">
                        @if($card->used_by)
                        <i class="fas fa-user ml-1"></i> {{ $card->user->name ?? 'غير متاح' }}
                        @else
                        <span class="text-gray-400">لم يتم الاستخدام</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500">
                        {{ $card->used_at ? $card->used_at->format('Y-m-d') : '-' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-credit-card empty-state-icon"></i>
                            <p class="empty-state-text">لا يوجد كروت</p>
                        </div>
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
<div id="generateModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>توليد كروت جديدة</h3>
        </div>
        <form method="POST" action="{{ url('/admin/credit-cards/generate') }}">
            @csrf
            <div class="modal-body">
                <div class="mb-4">
                    <label class="form-label">العدد</label>
                    <input type="number" name="count" min="1" max="1000" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">القيمة (د.ل)</label>
                    <select name="value" required class="form-select">
                        <option value="">اختر القيمة</option>
                        <option value="10">10 د.ل</option>
                        <option value="25">25 د.ل</option>
                        <option value="50">50 د.ل</option>
                        <option value="100">100 د.ل</option>
                        <option value="250">250 د.ل</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-primary">توليد</button>
                <button type="button" onclick="closeGenerateModal()" class="btn-neutral">إلغاء</button>
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
