@extends('layouts.teacher')
@section('title', isset($challenge) ? 'تعديل التحدي' : 'تحدي جديد')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl">
    <h2 class="text-2xl font-bold mb-6">{{ isset($challenge) ? 'تعديل التحدي' : 'تحدي جديد' }}</h2>
    <form method="POST" action="{{ isset($challenge) ? route('teacher.challenges.update', $challenge) : route('teacher.challenges.store') }}">
        @csrf
        @if(isset($challenge)) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">العنوان</label>
                <input type="text" name="title" value="{{ old('title', $challenge->title ?? '') }}" required class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">النوع</label>
                <select name="type" required class="shadow border rounded w-full py-2 px-3">
                    <option value="quiz_streak" @selected(old('type', $challenge->type ?? '') === 'quiz_streak')>)استمرارية اختبارات</option>
                    <option value="lesson_completion" @selected(old('type', $challenge->type ?? '') === 'lesson_completion')>إكمال دروس</option>
                    <option value="participation" @selected(old('type', $challenge->type ?? '') === 'participation')>مشاركة</option>
                    <option value="enrollment" @selected(old('type', $challenge->type ?? '') === 'enrollment')>تسجيل</option>
                </select>
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">الوصف</label>
            <textarea name="description" rows="3" class="shadow border rounded w-full py-2 px-3">{{ old('description', $challenge->description ?? '') }}</textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">الدورة (اختياري)</label>
            <select name="course_id" class="shadow border rounded w-full py-2 px-3">
                <option value="">عام</option>
                @foreach($courses as $c)
                <option value="{{ $c->id }}" @selected(old('course_id', $challenge->course_id ?? '') == $c->id)>{{ $c->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">نوع الهدف</label>
                <input type="text" name="goal_type" value="{{ old('goal_type', $challenge->goal_type ?? '') }}" required placeholder="e.g. streak_days" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">قيمة الهدف</label>
                <input type="number" name="goal_value" value="{{ old('goal_value', $challenge->goal_value ?? 1) }}" required min="1" class="shadow border rounded w-full py-2 px-3">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">تاريخ البداية</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', isset($challenge) && $challenge->starts_at ? $challenge->starts_at->format('Y-m-d\TH:i') : '') }}" required class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">تاريخ النهاية</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', isset($challenge) && $challenge->ends_at ? $challenge->ends_at->format('Y-m-d\TH:i') : '') }}" required class="shadow border rounded w-full py-2 px-3">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">نوع المكافأة</label>
                <select name="reward_type" required class="shadow border rounded w-full py-2 px-3">
                    <option value="xp" @selected(old('reward_type', $challenge->reward_type ?? '') === 'xp')>XP</option>
                    <option value="badge" @selected(old('reward_type', $challenge->reward_type ?? '') === 'badge')>شارة</option>
                    <option value="certificate" @selected(old('reward_type', $challenge->reward_type ?? '') === 'certificate')>شهادة</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">قيمة المكافأة</label>
                <input type="number" name="reward_value" value="{{ old('reward_value', $challenge->reward_value ?? 100) }}" required min="1" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الحد الأقصى للمشاركين</label>
                <input type="number" name="max_participants" value="{{ old('max_participants', $challenge->max_participants ?? '') }}" class="shadow border rounded w-full py-2 px-3">
            </div>
        </div>
        <div class="flex space-x-4 space-x-reverse">
            <button type="submit" class="btn-primary px-6 py-2 rounded font-bold">
                {{ isset($challenge) ? 'تحديث' : 'إنشاء' }}
            </button>
            <a href="{{ route('teacher.challenges.index') }}" class="bg-gray-300 hover:bg-gray-400 px-6 py-2 rounded font-bold">إلغاء</a>
        </div>
    </form>
</div>
@endsection
