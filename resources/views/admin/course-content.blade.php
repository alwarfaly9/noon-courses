@extends('layouts.admin')

@section('title', 'محتوى الدورة')

@section('content')
<div class="space-y-6">
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc pr-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">{{ $course->title }}</h2>
                <p class="text-gray-600">إدارة الأقسام والمحاضرات</p>
            </div>
            <a href="{{ route('admin.courses') }}" class="text-green-600 hover:text-green-800 font-semibold">عودة إلى قائمة الدورات</a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">إضافة قسم جديد</h3>
        <form method="POST" action="{{ route('admin.courses.sections.store', $course->id) }}" class="flex items-center space-x-3 space-x-reverse">
            @csrf
            <input type="text" name="title" class="flex-1 px-4 py-2 border rounded" placeholder="عنوان القسم" required>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">إضافة قسم</button>
        </form>
    </div>

    @foreach($course->sections as $section)
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-xl font-semibold">قسم: {{ $section->title }}</h4>
            <form method="POST" action="{{ route('admin.sections.delete', $section->id) }}" onsubmit="return confirm('تأكيد حذف القسم؟');">
                @csrf
                <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i> حذف القسم</button>
            </form>
        </div>

        <div class="space-y-4">
            @foreach($section->lessons as $lesson)
            <div class="border rounded p-4">
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

                <div class="mt-3 bg-gray-50 rounded p-3">
                    <form method="POST" action="{{ route('admin.lessons.upload', $lesson->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                        @csrf
                        <input type="file" name="file" accept="video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar-compressed,audio/*" class="md:col-span-3 border rounded px-3 py-2" placeholder="اختر الفيديو/الملف">
                        <input type="file" name="subtitle" accept=".vtt,.srt,text/vtt" class="md:col-span-2 border rounded px-3 py-2" placeholder="ملف الترجمة (اختياري)">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">رفع</button>
                    </form>
                    <p class="text-xs text-gray-500 mt-2">الفيديو: mp4/mov/mkv/webm حتى 500MB. الترجمة: vtt/srt حتى 10MB.</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4 bg-gray-50 rounded p-4">
            <h5 class="font-semibold mb-2">إضافة محاضرة</h5>
            <form method="POST" action="{{ route('admin.sections.lessons.store', $section->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                @csrf
                <input type="text" name="title" class="px-4 py-2 border rounded md:col-span-2" placeholder="عنوان الفيديو" required>
                <input type="text" name="duration_text" class="px-4 py-2 border rounded" placeholder="المدة (مثال: 45:00)">
                <input type="file" name="video_file" accept="video/*" class="px-4 py-2 border rounded" placeholder="فيديو (اختياري)">
                <input type="file" name="subtitle_file" accept=".vtt,.srt,text/vtt" class="px-4 py-2 border rounded" placeholder="ملف الترجمة (اختياري)">
                <textarea name="description" rows="2" class="md:col-span-6 px-4 py-2 border rounded" placeholder="وصف مختصر (اختياري)"></textarea>
                <div class="md:col-span-6">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">إضافة محاضرة</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endsection


