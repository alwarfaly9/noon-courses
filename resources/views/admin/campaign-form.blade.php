@extends('layouts.admin')
@section('title', isset($campaign) ? 'تعديل الحملة' : 'حملة جديدة')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl">
    <h2 class="text-2xl font-bold mb-6">{{ isset($campaign) ? 'تعديل الحملة' : 'حملة جديدة' }}</h2>
    <form method="POST" action="{{ isset($campaign) ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}">
        @csrf
        @if(isset($campaign)) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                <input type="text" name="name" value="{{ old('name', $campaign->name ?? '') }}" required class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الرابط المختصر (slug)</label>
                <input type="text" name="slug" value="{{ old('slug', $campaign->slug ?? '') }}" required class="shadow border rounded w-full py-2 px-3">
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">الوصف</label>
            <textarea name="description" rows="3" class="shadow border rounded w-full py-2 px-3">{{ old('description', $campaign->description ?? '') }}</textarea>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">النوع</label>
                <select name="type" required class="shadow border rounded w-full py-2 px-3">
                    <option value="weekly_challenge" @selected(old('type', $campaign->type ?? '') === 'weekly_challenge')>تحدي أسبوعي</option>
                    <option value="monthly" @selected(old('type', $campaign->type ?? '') === 'monthly')>شهري</option>
                    <option value="seasonal" @selected(old('type', $campaign->type ?? '') === 'seasonal')>موسمي</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">رابط الصورة</label>
                <input type="url" name="banner_image_url" value="{{ old('banner_image_url', $campaign->banner_image_url ?? '') }}" class="shadow border rounded w-full py-2 px-3">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">نقاط XP</label>
                <input type="number" name="reward_xp" value="{{ old('reward_xp', $campaign->reward_xp ?? 0) }}" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الشارة (اختياري)</label>
                <select name="reward_badge_id" class="shadow border rounded w-full py-2 px-3">
                    <option value="">بدون شارة</option>
                    @foreach($badges as $b)
                    <option value="{{ $b->id }}" @selected(old('reward_badge_id', $campaign->reward_badge_id ?? '') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">نوع الهدف</label>
                <input type="text" name="goal_type" value="{{ old('goal_type', $campaign->goal_type ?? '') }}" required class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">قيمة الهدف</label>
                <input type="number" name="goal_value" value="{{ old('goal_value', $campaign->goal_value ?? 1) }}" required min="1" class="shadow border rounded w-full py-2 px-3">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">تاريخ البداية</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', isset($campaign) && $campaign->starts_at ? $campaign->starts_at->format('Y-m-d\TH:i') : '') }}" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">تاريخ النهاية</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', isset($campaign) && $campaign->ends_at ? $campaign->ends_at->format('Y-m-d\TH:i') : '') }}" class="shadow border rounded w-full py-2 px-3">
            </div>
        </div>
        <div class="mb-4">
            <label class="flex items-center space-x-2 space-x-reverse">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $campaign->is_active ?? true))>
                <span class="mr-2">نشط</span>
            </label>
        </div>
        <div class="flex space-x-4 space-x-reverse">
            <button type="submit" class="btn-primary px-6 py-2 rounded font-bold">
                {{ isset($campaign) ? 'تحديث' : 'إنشاء' }}
            </button>
            <a href="{{ route('admin.campaigns.index') }}" class="bg-gray-300 hover:bg-gray-400 px-6 py-2 rounded font-bold">إلغاء</a>
        </div>
    </form>
</div>
@endsection
