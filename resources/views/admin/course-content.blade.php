@extends('layouts.admin')

@section('title', 'محتوى الدورة')

@section('content')
<div class="space-y-6">
    @if ($errors->any())
        <div class="alert-danger">
            <ul class="list-disc pr-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">{{ $course->title }}</h2>
                <p class="text-gray-600">إدارة الأقسام والمحاضرات</p>
            </div>
            <a href="{{ route('admin.courses') }}" class="text-green-600 hover:text-green-800 font-semibold">عودة إلى قائمة الدورات</a>
        </div>
    </div>

    <div class="card">
        <h3 class="text-lg font-semibold mb-4">إضافة قسم جديد</h3>
        <form method="POST" action="{{ route('admin.courses.sections.store', $course->id) }}" class="flex items-center space-x-3 space-x-reverse">
            @csrf
            <input type="text" name="title" class="form-input flex-1" placeholder="عنوان القسم" required>
            <button type="submit" class="btn-primary">إضافة قسم</button>
        </form>
    </div>

    @foreach($course->sections as $section)
    <div class="content-section">
        <div class="content-section-header">
            <div class="flex items-center justify-between w-full">
                <h4 class="text-xl font-semibold">قسم: {{ $section->title }}</h4>
                <form method="POST" action="{{ route('admin.sections.delete', $section->id) }}" onsubmit="return confirm('تأكيد حذف القسم؟');">
                    @csrf
                    <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i> حذف القسم</button>
                </form>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($section->lessons as $lesson)
            <div class="lesson-card">
                <div class="flex items-start justify-between">
                    <div>
                    <div class="font-semibold">{{ $lesson->title }}</div>
                    @if($lesson->duration)
                    <div class="text-sm text-gray-500">المدة: {{ gmdate('H:i:s', $lesson->duration) }}</div>
                    @endif
                    @if($lesson->description)
                    <div class="text-sm text-gray-700 mt-2">{{ Str::limit($lesson->description, 140) }}</div>
                    @endif
                    @if($lesson->content_url)
                    <div class="mt-2">
                        <video controls class="w-full max-w-xl rounded">
                            <source src="{{ $lesson->content_url }}" type="video/mp4">
                            @if($lesson->subtitle_file)
                                <track src="{{ $lesson->subtitle_file }}" kind="subtitles" srclang="ar" label="Arabic" default>
                            @endif
                        </video>
                    </div>
                    @elseif($lesson->content_file)
                    <div class="mt-2">
                        <a href="{{ $lesson->content_file }}" target="_blank" class="text-blue-600 hover:text-blue-800">مشاهدة/تحميل الملف</a>
                    </div>
                    @endif
                </div>
                <div class="flex items-center space-x-3 space-x-reverse">
                    <form method="POST" action="{{ route('admin.lessons.delete', $lesson->id) }}" onsubmit="return confirm('تأكيد حذف المحاضرة؟');">
                        @csrf
                        <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
                </div>

                <div class="upload-zone-sm mt-3">
                    <form method="POST" action="{{ route('admin.lessons.upload', $lesson->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                        @csrf
                        <input type="file" name="file" accept="video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar-compressed,audio/*" class="md:col-span-3 form-input px-3 py-2">
                        <input type="file" name="subtitle" accept=".vtt,.srt,text/vtt" class="md:col-span-2 form-input px-3 py-2">
                        <button type="submit" class="btn-primary">رفع</button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2">الفيديو: mp4/mov/mkv/webm حتى 500MB. الترجمة: vtt/srt حتى 10MB.</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="upload-zone mt-4">
            <h5 class="font-semibold mb-2">إضافة محاضرة</h5>
            <form method="POST" action="{{ route('admin.sections.lessons.store', $section->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                @csrf
                <input type="text" name="title" class="form-input px-4 py-2 md:col-span-2" placeholder="عنوان الفيديو" required>
                <input type="text" name="duration_text" class="form-input px-4 py-2" placeholder="المدة (مثال: 45:00)">
                <input type="file" name="video_file" accept="video/*" class="form-input px-4 py-2">
                <input type="file" name="subtitle_file" accept=".vtt,.srt,text/vtt" class="form-input px-4 py-2">
                <textarea name="description" rows="2" class="form-textarea md:col-span-6" placeholder="وصف مختصر (اختياري)"></textarea>
                <div class="md:col-span-6">
                    <button type="submit" class="btn-primary">إضافة محاضرة</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endsection
