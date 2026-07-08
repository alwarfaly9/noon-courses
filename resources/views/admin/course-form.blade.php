@extends('layouts.admin')

@section('title', $mode === 'create' ? 'إضافة دورة' : 'تعديل دورة')

@section('content')
<div class="card">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-book text-green-600 ml-2"></i>
            {{ $mode === 'create' ? 'إضافة دورة جديدة' : 'تعديل الدورة' }}
        </h2>
    </div>

    <form method="POST" action="{{ $mode === 'create' ? route('admin.courses.store') : route('admin.courses.update', $course->id) }}" class="space-y-6">
        @csrf

        @if ($errors->any())
            <div class="alert-danger">
                <ul class="list-disc pr-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label">عنوان الدورة</label>
                <input type="text" name="title" value="{{ old('title', $course->title) }}" class="form-input w-full" required>
            </div>
            <div>
                <label class="form-label">التصنيف</label>
                <select name="category_id" class="form-select w-full" required>
                    <option value="">اختر التصنيف</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (int) old('category_id', $course->category_id) === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">المحاضر</label>
                @if(auth()->user()->hasRole('teacher'))
                    <input type="hidden" name="teacher_id" value="{{ auth()->id() }}">
                    <input type="text" value="{{ auth()->user()->name }}" class="form-input w-full bg-gray-100" readonly>
                @else
                <select name="teacher_id" class="form-select w-full" required>
                    <option value="">اختر المحاضر</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}" {{ (int) old('teacher_id', $course->teacher_id) === $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            <div>
                <label class="form-label">المستوى</label>
                <select name="level" class="form-select w-full" required>
                    @php $level = old('level', $course->level ?? 'beginner'); @endphp
                    <option value="beginner" {{ $level==='beginner' ? 'selected' : '' }}>مبتدئ</option>
                    <option value="intermediate" {{ $level==='intermediate' ? 'selected' : '' }}>متوسط</option>
                    <option value="advanced" {{ $level==='advanced' ? 'selected' : '' }}>متقدم</option>
                </select>
            </div>
            <div>
                <label class="form-label">اللغة</label>
                <select name="language" class="form-select w-full" required>
                    @php $lang = old('language', $course->language ?? 'ar'); @endphp
                    <option value="ar" {{ $lang==='ar' ? 'selected' : '' }}>العربية</option>
                    <option value="en" {{ $lang==='en' ? 'selected' : '' }}>الإنجليزية</option>
                </select>
            </div>
            <div>
                <label class="form-label">السعر</label>
                <input type="number" step="0.01" name="price" value="{{ old('price', $course->price) }}" class="form-input w-full" required>
            </div>
            <div>
                <label class="form-label">سعر مخفّض (اختياري)</label>
                <input type="number" step="0.01" name="discount_price" value="{{ old('discount_price', $course->discount_price) }}" class="form-input w-full">
            </div>
        </div>

        <div>
            <label class="form-label">وصف الدورة</label>
            <textarea name="description" rows="5" class="form-textarea w-full" required>{{ old('description', $course->description) }}</textarea>
        </div>

        <div>
            <label class="form-label">نبذة مختصرة</label>
            <textarea name="short_description" rows="3" class="form-textarea w-full">{{ old('short_description', $course->short_description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="form-label">المتطلبات (سطر لكل متطلب)</label>
                <textarea name="requirements_text" rows="6" class="form-textarea w-full" placeholder="مثال:&#10;- معرفة أساسية بالحاسوب&#10;- اتصال إنترنت جيد">{{ old('requirements_text', is_array($course->requirements) ? implode("\n", $course->requirements) : '') }}</textarea>
            </div>
            <div>
                <label class="form-label">ماذا ستتعلم؟ (سطر لكل فائدة)</label>
                <textarea name="learn_text" rows="6" class="form-textarea w-full" placeholder="مثال:&#10;- إنشاء مشروع Laravel&#10;- بناء REST API">{{ old('learn_text', is_array($course->what_you_will_learn) ? implode("\n", $course->what_you_will_learn) : '') }}</textarea>
            </div>
        </div>

        @if($mode === 'edit')
        <div>
            <label class="form-label">الحالة</label>
            @php $status = old('status', $course->status); @endphp
            @if(auth()->user()->hasRole('admin'))
            <select name="status" class="form-select w-full">
                <option value="draft" {{ $status==='draft' ? 'selected' : '' }}>مسوّدة</option>
                <option value="pending" {{ $status==='pending' ? 'selected' : '' }}>قيد المراجعة</option>
                <option value="published" {{ $status==='published' ? 'selected' : '' }}>منشور</option>
                <option value="rejected" {{ $status==='rejected' ? 'selected' : '' }}>مرفوض</option>
            </select>
            @else
            <div class="form-input w-full bg-gray-100 font-medium">
                @if($status == 'published') <span class="text-green-600">منشور</span>
                @elseif($status == 'pending') <span class="text-yellow-600">قيد المراجعة</span>
                @elseif($status == 'rejected') <span class="text-red-600">مرفوض</span>
                @else {{ $status }} @endif
            </div>
            @endif
        </div>
        @endif

        <div class="flex items-center space-x-3 space-x-reverse">
            <a href="{{ route('admin.courses') }}" class="btn-secondary">إلغاء</a>
            <button type="submit" class="btn-primary">
                {{ $mode === 'create' ? 'حفظ' : 'تحديث' }}
            </button>
        </div>
    </form>

    <div class="content-section mt-10">
        <div class="content-section-header">
            <h3>محتوى الدورة (الأقسام والمحاضرات)</h3>
        </div>
        @if($mode === 'create')
            <p class="text-sm text-gray-600">بعد حفظ الدورة، ستتمكن من إضافة الأقسام والمحاضرات ورفع الفيديوهات والملفات.</p>
        @else
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm text-gray-600">يمكنك إدارة المحتوى مباشرة من هنا أو من خلال صفحة مخصّصة.</p>
                <a href="{{ route('admin.courses.content', $course->id) }}" class="text-green-700 hover:text-green-900 font-semibold">فتح مدير المحتوى الكامل</a>
            </div>

            <div class="card mb-4 p-4">
                <h4 class="font-semibold mb-3">إضافة قسم جديد</h4>
                <form method="POST" action="{{ route('admin.courses.sections.store', $course->id) }}" class="flex items-center space-x-3 space-x-reverse">
                    @csrf
                    <input type="text" name="title" class="form-input flex-1" placeholder="عنوان القسم" required>
                    <button type="submit" class="btn-primary">إضافة قسم</button>
                </form>
            </div>

            @php $course->loadMissing(['sections.lessons' => function($q){ $q->orderBy('order'); }]); @endphp
            @foreach($course->sections as $section)
                <div class="lesson-card mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <h5 class="font-semibold">قسم: {{ $section->title }}</h5>
                        <form method="POST" action="{{ route('admin.sections.delete', $section->id) }}" onsubmit="return confirm('تأكيد حذف القسم؟');">
                            @csrf
                            <button class="text-red-600 hover:text-red-800 text-sm">حذف القسم</button>
                        </form>
                    </div>

                    <div class="space-y-3">
                        @foreach($section->lessons as $lesson)
                            <div class="border rounded p-3">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="font-medium">{{ $lesson->title }}</div>
                                        @if($lesson->duration)
                                            <div class="text-xs text-gray-500">المدة: {{ gmdate('H:i:s', $lesson->duration) }}</div>
                                        @endif
                                        @if($lesson->description)
                                            <div class="text-xs text-gray-700 mt-1">{{ Str::limit($lesson->description, 120) }}</div>
                                        @endif
                                        @if($lesson->content_url)
                                            <div class="mt-2">
                                                <video controls class="w-full max-w-md rounded">
                                                    <source src="{{ $lesson->content_url }}" type="video/mp4">
                                                    @if($lesson->subtitle_file)
                                                        <track src="{{ $lesson->subtitle_file }}" kind="subtitles" srclang="ar" label="Arabic" default>
                                                    @endif
                                                </video>
                                            </div>
                                        @elseif($lesson->content_file)
                                            <div class="mt-2">
                                                <a href="{{ $lesson->content_file }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">مشاهدة/تحميل الملف</a>
                                            </div>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('admin.lessons.delete', $lesson->id) }}" onsubmit="return confirm('تأكيد حذف المحاضرة؟');">
                                        @csrf
                                        <button class="text-red-600 hover:text-red-800 text-sm">حذف</button>
                                    </form>
                                </div>

                                <div class="upload-zone-sm mt-3">
                                    <form method="POST" action="{{ route('admin.lessons.upload', $lesson->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                                        @csrf
                                        <input type="file" name="file" accept="video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar-compressed,audio/*" class="md:col-span-3 border rounded px-3 py-2 form-input">
                                        <input type="file" name="subtitle" accept=".vtt,.srt,text/vtt" class="md:col-span-2 border rounded px-3 py-2 form-input">
                                        <button type="submit" class="btn-primary">رفع</button>
                                    </form>
                                    <p class="text-[10px] text-gray-500 mt-2">الفيديو: mp4/mov/mkv/webm حتى 500MB. الترجمة: vtt/srt حتى 10MB.</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="upload-zone-sm mt-3">
                        <h6 class="font-semibold mb-2 text-sm">إضافة محاضرة</h6>
                        <form method="POST" action="{{ route('admin.sections.lessons.store', $section->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                            @csrf
                            <input type="text" name="title" class="form-input px-3 py-2 md:col-span-2" placeholder="عنوان الفيديو" required>
                            <input type="text" name="duration_text" class="form-input px-3 py-2" placeholder="المدة (مثال: 45:00)">
                            <input type="file" name="video_file" accept="video/*" class="form-input px-3 py-2">
                            <input type="file" name="subtitle_file" accept=".vtt,.srt,text/vtt" class="form-input px-3 py-2">
                            <textarea name="description" rows="2" class="form-textarea md:col-span-6" placeholder="وصف مختصر (اختياري)"></textarea>
                            <div class="md:col-span-6">
                                <button type="submit" class="btn-primary">إضافة محاضرة</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection
