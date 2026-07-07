@extends('layouts.admin')
@section('title', 'الإحالات')
@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-purple-500">
            <div class="text-sm text-gray-500">إجمالي الإحالات</div>
            <div class="text-3xl font-bold text-purple-600">{{ $totalReferrals }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-yellow-500">
            <div class="text-sm text-gray-500">قيد الانتظار</div>
            <div class="text-3xl font-bold text-yellow-600">{{ $pendingCount }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-green-500">
            <div class="text-sm text-gray-500">مُكافأة</div>
            <div class="text-3xl font-bold text-green-600">{{ $rewardedCount }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-blue-500">
            <div class="text-sm text-gray-500">إجمالي المكافآت</div>
            <div class="text-3xl font-bold text-blue-600">{{ number_format($totalRewards, 2) }} د.ل</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-orange-500">
            <div class="text-sm text-gray-500">معدل التحويل</div>
            <div class="text-3xl font-bold text-orange-600">{{ $conversionRate }}%</div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-cog text-purple-600 mr-3"></i>
            إعدادات المكافآت
        </h3>
        <form method="POST" action="{{ route('admin.referrals.settings') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">قيمة المكافأة (د.ل)</label>
                <input type="number" name="reward_amount" step="0.01" value="{{ $settings->reward_amount }}" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">نوع المكافأة</label>
                <select name="reward_type" class="shadow border rounded w-full py-2 px-3">
                    <option value="wallet" @selected($settings->reward_type === 'wallet')>محفظة</option>
                    <option value="xp" @selected($settings->reward_type === 'xp')>نقاط XP</option>
                    <option value="both" @selected($settings->reward_type === 'both')>محفظة + XP</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">نقاط XP (إن وجد)</label>
                <input type="number" name="xp_reward" value="{{ $settings->xp_reward }}" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">الحد الأقصى لكل مستخدم</label>
                <input type="number" name="max_rewards_per_user" value="{{ $settings->max_rewards_per_user }}" placeholder="غير محدود" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="btn-primary px-6 py-2 rounded font-bold">حفظ الإعدادات</button>
            </div>
        </form>
    </div>

    <!-- Top Referrers -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-crown text-yellow-500 mr-3"></i>
            أفضل المسوّقين
        </h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right">#</th>
                        <th class="px-6 py-3 text-right">الاسم</th>
                        <th class="px-6 py-3 text-right">إجمالي الإحالات</th>
                        <th class="px-6 py-3 text-right">الناجحة</th>
                        <th class="px-6 py-3 text-right">المكاسب</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($topReferrers as $i => $r)
                    <tr>
                        <td class="px-6 py-4">{{ $i + 1 }}</td>
                        <td class="px-6 py-4 font-medium">{{ $r->name }}</td>
                        <td class="px-6 py-4">{{ $r->total_referrals }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">{{ $r->successful }}</span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-green-600">{{ number_format($r->total_earned, 2) }} د.ل</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
