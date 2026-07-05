@extends('layouts.teacher')
@section('title', isset($course) ? 'تعديل الدورة' : 'إضافة دورة جديدة')
@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
        <i class="fas fa-{{ isset($course) ? 'edit' : 'plus-circle' }} text-purple-600"></i>
        {{ isset($course) ? 'تعديل الدورة: '.$course->title : 'إضافة دورة جديدة' }}
    </h2>

    <form method="POST"
          action="{{ isset($course) ? route('teacher.courses.update', $course->id) : route('teacher.courses.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if(isset($course)) @method('PUT') @endif

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <ul class="list-disc pr-6 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Title -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">عنوان الدورة <span class="text-red-500">*</span></label>
                <input type="text" name="title"
                       value="{{ old('title', $course->title ?? '') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
            </div>

            <!-- Category -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">التصنيف <span class="text-red-500">*</span></label>
                <select name="category_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
                    <option value="">اختر التصنيف</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                        {{ (int)old('category_id', $course->category_id ?? 0) === $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Level -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المستوى <span class="text-red-500">*</span></label>
                @php $lvl = old('level', $course->level ?? 'beginner'); @endphp
                <select name="level" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
                    <option value="beginner"     {{ $lvl==='beginner'     ? 'selected' : '' }}>مبتدئ</option>
                    <option value="intermediate" {{ $lvl==='intermediate' ? 'selected' : '' }}>متوسط</option>
                    <option value="advanced"     {{ $lvl==='advanced'     ? 'selected' : '' }}>متقدم</option>
                </select>
            </div>

            <!-- Language -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">اللغة <span class="text-red-500">*</span></label>
                @php $lang = old('language', $course->language ?? 'ar'); @endphp
                <select name="language" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
                    <option value="ar" {{ $lang==='ar' ? 'selected' : '' }}>العربية</option>
                    <option value="en" {{ $lang==='en' ? 'selected' : '' }}>الإنجليزية</option>
                </select>
            </div>

            <!-- Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">السعر (د.ل) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="price"
                       value="{{ old('price', $course->price ?? '') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
            </div>

            <!-- Discount Price -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">سعر الخصم (اختياري)</label>
                <input type="number" step="0.01" min="0" name="discount_price"
                       value="{{ old('discount_price', $course->discount_price ?? '') }}"
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300">
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">وصف الدورة <span class="text-red-500">*</span></label>
            <textarea name="description" rows="5"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300"
                      required>{{ old('description', $course->description ?? '') }}</textarea>
        </div>

        <!-- Short Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">وصف مختصر</label>
            <textarea name="short_description" rows="2"
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300">{{ old('short_description', $course->short_description ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Requirements -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المتطلبات (سطر لكل متطلب)</label>
                <textarea name="requirements_text" rows="5"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300"
                          placeholder="معرفة أساسية بالحاسوب&#10;اتصال إنترنت جيد">{{ old('requirements_text', isset($course) ? implode("\n", (array)($course->requirements ?? [])) : '') }}</textarea>
            </div>

            <!-- What You Learn -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ماذا ستتعلم؟ (سطر لكل فائدة)</label>
                <textarea name="learn_text" rows="5"
                          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300"
                          placeholder="بناء تطبيقات حديثة&#10;إنشاء REST API">{{ old('learn_text', isset($course) ? implode("\n", (array)($course->what_you_will_learn ?? [])) : '') }}</textarea>
            </div>
        </div>

        <!-- Image Upload -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">صورة الدورة</label>
            @if(isset($course) && $course->image)
            <div class="mb-2">
                <img src="{{ asset('storage/'.$course->image) }}" class="h-24 rounded-lg object-cover">
                <p class="text-xs text-gray-400 mt-1">الصورة الحالية — ارفع صورة جديدة لاستبدالها</p>
            </div>
            @endif
            <input type="file" name="image" accept="image/*"
                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300">
        </div>

        <div class="flex gap-3">
            <a href="{{ route('teacher.courses') }}"
               class="px-4 py-2 border rounded-lg hover:bg-gray-50">إلغاء</a>
            <button type="submit"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">
                {{ isset($course) ? 'حفظ التعديلات' : 'إرسال للمراجعة' }}
            </button>
        </div>
    </form>
</div>
@endsection
