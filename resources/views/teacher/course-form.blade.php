@extends('layouts.teacher')
@section('title', isset($course) ? 'تعديل الدورة' : 'إضافة دورة جديدة')
@section('content')
<div class="card">
    <div class="card-header">
        <i class="fas fa-{{ isset($course) ? 'edit' : 'plus-circle' }} text-purple-600"></i>
        {{ isset($course) ? 'تعديل الدورة: '.$course->title : 'إضافة دورة جديدة' }}
    </div>
    <div class="card-body">
        <form method="POST"
              action="{{ isset($course) ? route('teacher.courses.update', $course->id) : route('teacher.courses.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if(isset($course)) @method('PUT') @endif

            @if($errors->any())
            <div class="alert-danger">
                <ul class="list-disc pr-6 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="form-label">عنوان الدورة <span class="text-red-500">*</span></label>
                    <input type="text" name="title"
                           value="{{ old('title', $course->title ?? '') }}"
                           class="form-input" required>
                </div>

                <div>
                    <label class="form-label">التصنيف <span class="text-red-500">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">اختر التصنيف</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}"
                            {{ (int)old('category_id', $course->category_id ?? 0) === $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">المستوى <span class="text-red-500">*</span></label>
                    @php $lvl = old('level', $course->level ?? 'beginner'); @endphp
                    <select name="level" class="form-select" required>
                        <option value="beginner"     {{ $lvl==='beginner'     ? 'selected' : '' }}>مبتدئ</option>
                        <option value="intermediate" {{ $lvl==='intermediate' ? 'selected' : '' }}>متوسط</option>
                        <option value="advanced"     {{ $lvl==='advanced'     ? 'selected' : '' }}>متقدم</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">اللغة <span class="text-red-500">*</span></label>
                    @php $lang = old('language', $course->language ?? 'ar'); @endphp
                    <select name="language" class="form-select" required>
                        <option value="ar" {{ $lang==='ar' ? 'selected' : '' }}>العربية</option>
                        <option value="en" {{ $lang==='en' ? 'selected' : '' }}>الإنجليزية</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">السعر (د.ل) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" name="price"
                           value="{{ old('price', $course->price ?? '') }}"
                           class="form-input" required>
                </div>

                <div>
                    <label class="form-label">سعر الخصم (اختياري)</label>
                    <input type="number" step="0.01" min="0" name="discount_price"
                           value="{{ old('discount_price', $course->discount_price ?? '') }}"
                           class="form-input">
                </div>
            </div>

            <div>
                <label class="form-label">وصف الدورة <span class="text-red-500">*</span></label>
                <textarea name="description" rows="5"
                          class="form-textarea"
                          required>{{ old('description', $course->description ?? '') }}</textarea>
            </div>

            <div>
                <label class="form-label">وصف مختصر</label>
                <textarea name="short_description" rows="2"
                          class="form-textarea">{{ old('short_description', $course->short_description ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">المتطلبات (سطر لكل متطلب)</label>
                    <textarea name="requirements_text" rows="5"
                              class="form-textarea"
                              placeholder="معرفة أساسية بالحاسوب&#10;اتصال إنترنت جيد">{{ old('requirements_text', isset($course) ? implode("\n", (array)($course->requirements ?? [])) : '') }}</textarea>
                </div>

                <div>
                    <label class="form-label">ماذا ستتعلم؟ (سطر لكل فائدة)</label>
                    <textarea name="learn_text" rows="5"
                              class="form-textarea"
                              placeholder="بناء تطبيقات حديثة&#10;إنشاء REST API">{{ old('learn_text', isset($course) ? implode("\n", (array)($course->what_you_will_learn ?? [])) : '') }}</textarea>
                </div>
            </div>

            <div>
                <label class="form-label">صورة الدورة</label>
                @if(isset($course) && $course->image)
                <div class="mb-2">
                    <img src="{{ asset('storage/'.$course->image) }}" class="h-24 rounded-lg object-cover">
                    <p class="text-xs text-gray-400 mt-1">الصورة الحالية — ارفع صورة جديدة لاستبدالها</p>
                </div>
                @endif
                <input type="file" name="image" accept="image/*"
                       class="upload-zone">
            </div>

            <div class="card-footer">
                <a href="{{ route('teacher.courses') }}" class="btn-neutral">إلغاء</a>
                <button type="submit" class="btn-primary">
                    {{ isset($course) ? 'حفظ التعديلات' : 'إرسال للمراجعة' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
