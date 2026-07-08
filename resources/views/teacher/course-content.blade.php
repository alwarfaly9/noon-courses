@extends('layouts.teacher')
@section('title', 'محتوى الدورة: '.$course->title)

@section('content')
<div class="space-y-6">
    @if ($errors->any())
    <div class="alert-danger">
        <ul class="list-disc pr-6">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Course Header -->
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">{{ $course->title }}</h2>
                    <p class="text-gray-500 mt-1">إدارة الأقسام والمحاضرات</p>
                </div>
                <a href="{{ route('teacher.courses') }}"
                   class="text-purple-600 hover:text-purple-800 font-semibold flex items-center gap-1">
                    <i class="fas fa-arrow-right"></i> قائمة الدورات
                </a>
            </div>
        </div>
    </div>

    <!-- Add Section -->
    <div class="card">
        <div class="card-body">
            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-folder-plus text-purple-600"></i> إضافة قسم جديد
            </h3>
            <form method="POST" action="{{ route('teacher.courses.sections.store', $course->id) }}"
                  class="flex items-center gap-3">
                @csrf
                <input type="text" name="title"
                       class="form-input flex-1"
                       placeholder="عنوان القسم (مثال: مقدمة، الفصل الأول...)" required>
                <button type="submit" class="btn-primary whitespace-nowrap">
                    <i class="fas fa-plus me-1"></i> إضافة
                </button>
            </form>
        </div>
    </div>

    <!-- Sections List -->
    @forelse($course->sections as $section)
    <div class="card">
        <div class="card-body">
            <!-- Section Header -->
            <div class="flex items-center justify-between mb-4 pb-3 border-b">
                <div class="flex items-center gap-3">
                    <span class="bg-purple-100 text-purple-700 font-bold rounded-full w-8 h-8 flex items-center justify-center text-sm">
                        {{ $section->order }}
                    </span>
                    <h4 class="text-lg font-semibold">{{ $section->title }}</h4>
                    <span class="text-sm text-gray-400">({{ $section->lessons->count() }} محاضرة)</span>
                </div>
                <form method="POST" action="{{ route('teacher.sections.delete', $section->id) }}"
                      onsubmit="return confirm('سيتم حذف هذا القسم وجميع محاضراته. هل أنت متأكد؟')">
                    @csrf
                    <button class="text-red-500 hover:text-red-700 text-sm flex items-center gap-1">
                        <i class="fas fa-trash-alt"></i> حذف القسم
                    </button>
                </form>
            </div>

            <!-- Lessons List -->
            <div class="space-y-3 mb-6">
                @forelse($section->lessons as $lesson)
                <div class="lesson-card">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-{{ $lesson->type === 'video' ? 'play-circle text-purple-500' : 'file-alt text-blue-500' }}"></i>
                                <span class="font-semibold">{{ $lesson->title }}</span>
                                @if($lesson->duration)
                                <span class="badge-neutral text-xs">
                                    {{ gmdate('H:i:s', $lesson->duration) }}
                                </span>
                                @endif
                            </div>
                            @if($lesson->description)
                            <p class="text-sm text-gray-500 mt-1">{{ Str::limit($lesson->description, 120) }}</p>
                            @endif

                            @if($lesson->content_url)
                            <div class="mt-2">
                                <video controls class="w-full max-w-xl rounded-lg">
                                    <source src="{{ $lesson->content_url }}" type="video/mp4">
                                    @if($lesson->subtitle_file)
                                    <track src="{{ $lesson->subtitle_file }}" kind="subtitles" srclang="ar" label="Arabic" default>
                                    @endif
                                    المتصفح لا يدعم تشغيل الفيديو.
                                </video>
                            </div>
                            @elseif($lesson->content_file)
                            <div class="mt-2">
                                <a href="{{ $lesson->content_file }}" target="_blank"
                                   class="text-blue-600 hover:underline text-sm">
                                    <i class="fas fa-download me-1"></i> تحميل الملف
                                </a>
                            </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('teacher.lessons.delete', $lesson->id) }}"
                              onsubmit="return confirm('حذف المحاضرة؟')">
                            @csrf
                            <button class="text-red-400 hover:text-red-600 ms-4">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Upload file to existing lesson -->
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <form method="POST" action="{{ route('teacher.lessons.upload', $lesson->id) }}"
                              enctype="multipart/form-data"
                              class="grid grid-cols-1 md:grid-cols-6 gap-2">
                            @csrf
                            <input type="file" name="file"
                                   accept="video/*,application/pdf,.doc,.docx,.ppt,.pptx,.zip,.rar,audio/*"
                                   class="md:col-span-3 form-input text-sm">
                            <input type="file" name="subtitle" accept=".vtt,.srt"
                                   class="md:col-span-2 form-input text-sm">
                            <button type="submit" class="btn-sm btn-neutral">
                                رفع
                            </button>
                        </form>
                        <p class="text-xs text-gray-400 mt-1">فيديو/ملف (حتى 500MB) + ترجمة اختيارية (vtt/srt)</p>
                    </div>
                </div>
                @empty
                <p class="text-gray-400 text-sm italic">لا توجد محاضرات في هذا القسم بعد.</p>
                @endforelse
            </div>

            <!-- Add Lesson Form -->
            <div class="bg-purple-50 rounded-lg p-4">
                <h5 class="font-semibold mb-3 text-purple-800 flex items-center gap-2">
                    <i class="fas fa-video"></i> إضافة محاضرة جديدة
                </h5>
                <form method="POST" action="{{ route('teacher.sections.lessons.store', $section->id) }}"
                      enctype="multipart/form-data"
                      class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    @csrf
                    <input type="text" name="title"
                           class="form-input md:col-span-3"
                           placeholder="عنوان المحاضرة" required>
                    <input type="text" name="duration_text"
                           class="form-input"
                           placeholder="المدة (45:00)">
                    <input type="file" name="video_file" accept="video/*"
                           class="form-input md:col-span-2">
                    <input type="file" name="subtitle_file" accept=".vtt,.srt"
                           class="form-input md:col-span-2">
                    <textarea name="description" rows="2"
                              class="form-textarea md:col-span-4"
                              placeholder="وصف مختصر (اختياري)"></textarea>
                    <div class="md:col-span-6">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus me-1"></i> إضافة المحاضرة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center text-gray-400 py-10">
            <i class="fas fa-layer-group text-4xl mb-3 block"></i>
            لا توجد أقسام بعد. أضف قسماً أعلاه لبدء تنظيم محتوى دورتك.
        </div>
    </div>
    @endforelse
</div>
@endsection
