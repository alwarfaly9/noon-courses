@extends('layouts.teacher')
@section('title', isset($story) ? 'تعديل القصة' : 'قصة جديدة')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl">
    <h2 class="text-2xl font-bold mb-6">{{ isset($story) ? 'تعديل القصة' : 'قصة جديدة' }}</h2>
    <form method="POST" action="{{ isset($story) ? route('teacher.stories.update', $story) : route('teacher.stories.store') }}" enctype="multipart/form-data">
        @csrf
        @if(isset($story)) @method('PUT') @endif

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">العنوان</label>
            <input type="text" name="title" value="{{ old('title', $story->title ?? '') }}" required class="shadow border rounded w-full py-2 px-3">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">النص (اختياري)</label>
            <textarea name="body" rows="3" class="shadow border rounded w-full py-2 px-3">{{ old('body', $story->body ?? '') }}</textarea>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">الدورة (اختياري)</label>
            <select name="course_id" class="shadow border rounded w-full py-2 px-3">
                <option value="">قصة عامة</option>
                @foreach($courses as $c)
                <option value="{{ $c->id }}" @selected((old('course_id', $story->course_id ?? '') == $c->id))>{{ $c->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">الوسائط (صورة أو فيديو)</label>
            <input type="file" name="media" accept="image/*,video/*" class="shadow border rounded w-full py-2 px-3">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">تاريخ الانتهاء (اختياري)</label>
            <input type="datetime-local" name="expires_at" value="{{ old('expires_at', isset($story) && $story->expires_at ? $story->expires_at->format('Y-m-d\TH:i') : '') }}" class="shadow border rounded w-full py-2 px-3">
        </div>
        <div class="flex space-x-4 space-x-reverse">
            <button type="submit" class="btn-primary px-6 py-2 rounded font-bold">
                {{ isset($story) ? 'تحديث' : 'نشر' }}
            </button>
            <a href="{{ route('teacher.stories.index') }}" class="bg-gray-300 hover:bg-gray-400 px-6 py-2 rounded font-bold">إلغاء</a>
        </div>
    </form>
</div>
@endsection
