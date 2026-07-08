@extends('layouts.teacher')
@section('title', isset($story) ? 'تعديل القصة' : 'قصة جديدة')
@section('content')
<div class="card">
    <div class="card-header">
        <i class="fas fa-{{ isset($story) ? 'edit' : 'plus-circle' }} text-purple-600"></i>
        {{ isset($story) ? 'تعديل القصة' : 'قصة جديدة' }}
    </div>
    <div class="card-body">
        <form method="POST" action="{{ isset($story) ? route('teacher.stories.update', $story) : route('teacher.stories.store') }}" enctype="multipart/form-data">
            @csrf
            @if(isset($story)) @method('PUT') @endif

            <div class="mb-4">
                <label class="form-label">العنوان</label>
                <input type="text" name="title" value="{{ old('title', $story->title ?? '') }}" required class="form-input">
            </div>
            <div class="mb-4">
                <label class="form-label">النص (اختياري)</label>
                <textarea name="body" rows="3" class="form-textarea">{{ old('body', $story->body ?? '') }}</textarea>
            </div>
            <div class="mb-4">
                <label class="form-label">الدورة (اختياري)</label>
                <select name="course_id" class="form-select">
                    <option value="">قصة عامة</option>
                    @foreach($courses as $c)
                    <option value="{{ $c->id }}" @selected((old('course_id', $story->course_id ?? '') == $c->id))>{{ $c->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label">الوسائط (صورة أو فيديو)</label>
                <input type="file" name="media" accept="image/*,video/*" class="upload-zone">
            </div>
            <div class="mb-4">
                <label class="form-label">تاريخ الانتهاء (اختياري)</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', isset($story) && $story->expires_at ? $story->expires_at->format('Y-m-d\TH:i') : '') }}" class="form-input">
            </div>
            <div class="card-footer">
                <button type="submit" class="btn-primary">
                    {{ isset($story) ? 'تحديث' : 'نشر' }}
                </button>
                <a href="{{ route('teacher.stories.index') }}" class="btn-neutral">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
