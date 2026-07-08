@extends('layouts.admin')
@section('title', 'الإحالات')
@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="stat-card-premium border-r-4 border-purple-500">
            <div class="text-sm text-gray-500">إجمالي الإحالات</div>
            <div class="text-3xl font-bold text-purple-600">{{ $totalReferrals }}</div>
        </div>
        <div class="stat-card-premium border-r-4 border-yellow-500">
            <div class="text-sm text-gray-500">قيد الانتظار</div>
            <div class="text-3xl font-bold text-yellow-600">{{ $pendingCount }}</div>
        </div>
        <div class="stat-card-premium border-r-4 border-green-500">
            <div class="text-sm text-gray-500">مُكافأة</div>
            <div class="text-3xl font-bold text-green-600">{{ $rewardedCount }}</div>
        </div>
        <div class="stat-card-premium border-r-4 border-blue-500">
            <div class="text-sm text-gray-500">إجمالي المكافآت</div>
            <div class="text-3xl font-bold text-blue-600">{{ number_format($totalRewards, 2) }} د.ل</div>
        </div>
        <div class="stat-card-premium border-r-4 border-orange-500">
            <div class="text-sm text-gray-500">معدل التحويل</div>
            <div class="text-3xl font-bold text-orange-600">{{ $conversionRate }}%</div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="content-section">
        <div class="content-section-header">
            <h3><i class="fas fa-cog text-purple-600 mr-3"></i> إعدادات المكافآت</h3>
        </div>
        <form method="POST" action="{{ route('admin.referrals.settings') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div>
                <label class="form-label">قيمة المكافأة (د.ل)</label>
                <input type="number" name="reward_amount" step="0.01" value="{{ $settings->reward_amount }}" class="form-input">
            </div>
            <div>
                <label class="form-label">نوع المكافأة</label>
                <select name="reward_type" class="form-select">
                    <option value="wallet" @selected($settings->reward_type === 'wallet')>محفظة</option>
                    <option value="xp" @selected($settings->reward_type === 'xp')>نقاط XP</option>
                    <option value="both" @selected($settings->reward_type === 'both')>محفظة + XP</option>
                </select>
            </div>
            <div>
                <label class="form-label">نقاط XP (إن وجد)</label>
                <input type="number" name="xp_reward" value="{{ $settings->xp_reward }}" class="form-input">
            </div>
            <div>
                <label class="form-label">الحد الأقصى لكل مستخدم</label>
                <input type="number" name="max_rewards_per_user" value="{{ $settings->max_rewards_per_user }}" placeholder="غير محدود" class="form-input">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="btn-primary">حفظ الإعدادات</button>
            </div>
        </form>
    </div>

    <!-- Top Referrers -->
    <div class="content-section">
        <div class="content-section-header">
            <h3><i class="fas fa-crown text-yellow-500 mr-3"></i> أفضل المسوّقين</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table-dash">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>إجمالي الإحالات</th>
                        <th>الناجحة</th>
                        <th>المكاسب</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topReferrers as $i => $r)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="font-medium">{{ $r->name }}</td>
                        <td>{{ $r->total_referrals }}</td>
                        <td>
                            <span class="badge-success">{{ $r->successful }}</span>
                        </td>
                        <td class="font-semibold text-green-600">{{ number_format($r->total_earned, 2) }} د.ل</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
