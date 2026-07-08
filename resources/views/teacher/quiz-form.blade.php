@extends('layouts.teacher')
@section('title', isset($quiz) ? 'تعديل الاختبار' : 'اختبار جديد')
@push('head')
<style>
    .option-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
    .option-row input[type="text"] { flex: 1; }
</style>
@endpush
@section('content')
<div class="card">
    <div class="card-header">
        <i class="fas fa-{{ isset($quiz) ? 'edit' : 'plus-circle' }} text-purple-600"></i>
        {{ isset($quiz) ? 'تعديل الاختبار' : 'اختبار جديد' }}
    </div>
    <div class="card-body">
        @php $route = isset($quiz) ? route('teacher.quizzes.update', $quiz) : route('teacher.quizzes.store', $course ?? $quiz->section->course) @endphp
        <form method="POST" action="{{ $route }}">
            @csrf
            @if(isset($quiz)) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="form-label">عنوان الاختبار</label>
                    <input type="text" name="title" value="{{ old('title', $quiz->title ?? '') }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">القسم</label>
                    <select name="course_section_id" required class="form-select">
                        <option value="">اختر القسم</option>
                        @foreach($sections as $s)
                        <option value="{{ $s->id }}" @selected(old('course_section_id', $quiz->course_section_id ?? '') == $s->id)>{{ $s->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">الدرجة المطلوبة للنجاح (%)</label>
                    <input type="number" name="pass_mark" value="{{ old('pass_mark', $quiz->pass_mark ?? 50) }}" min="1" max="100" required class="form-input">
                </div>
                <div>
                    <label class="form-label">المدة (دقائق، اختياري)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $quiz->duration_minutes ?? '') }}" min="0" class="form-input">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">الوصف (اختياري)</label>
                <textarea name="description" rows="3" class="form-textarea">{{ old('description', $quiz->description ?? '') }}</textarea>
            </div>

            @if(isset($quiz))
            <input type="hidden" name="course_id" value="{{ $quiz->section->course_id }}">
            @else
            <input type="hidden" name="course_id" value="{{ $course->id }}">
            @endif

            {{-- Questions section --}}
            <div class="mt-6 border-t pt-6">
                <h3 class="text-xl font-bold mb-4 flex items-center">
                    <i class="fas fa-list text-purple-600 mr-2"></i>
                    الأسئلة
                </h3>

                @php $questions = old('questions', $quiz->questions ?? []) @endphp

                <div id="questions-container">
                    @foreach($questions as $qi => $q)
                    <div class="question-box border rounded-lg p-4 mb-4 bg-gray-50">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold text-purple-700">سؤال #{{ $loop->iteration }}</span>
                            <button type="button" class="text-red-500 hover:text-red-700 remove-question"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">نص السؤال</label>
                            <textarea name="questions[{{ $qi }}][content]" rows="2" required class="form-textarea">{{ is_string($q) ? '' : ($q['content'] ?? '') }}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div>
                                <label class="form-label">النوع</label>
                                <select name="questions[{{ $qi }}][type]" class="form-select question-type">
                                    <option value="multiple_choice" @selected((is_array($q) ? ($q['type'] ?? '') : '') == 'multiple_choice')>اختيار من متعدد</option>
                                    <option value="true_false" @selected((is_array($q) ? ($q['type'] ?? '') : '') == 'true_false')>صح/خطأ</option>
                                    <option value="fill_in_blank" @selected((is_array($q) ? ($q['type'] ?? '') : '') == 'fill_in_blank')>تعبئة فراغ</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">الدرجة</label>
                                <input type="number" name="questions[{{ $qi }}][score]" value="{{ is_array($q) ? ($q['score'] ?? 1) : 1 }}" min="1" class="form-input">
                            </div>
                        </div>
                        <div class="options-container">
                            <label class="form-label">الخيارات</label>
                            @php $options = is_array($q) ? ($q['options'] ?? []) : (method_exists($q, 'options') ? $q->options : []) @endphp
                            @foreach($options as $oi => $opt)
                            <div class="option-row">
                                <input type="radio" name="questions[{{ $qi }}][correct]" value="{{ $oi }}" @checked((is_array($opt) ? ($opt['is_correct'] ?? false) : ($opt->is_correct ?? false)))>
                                <input type="text" name="questions[{{ $qi }}][options][{{ $oi }}][text]" value="{{ is_array($opt) ? ($opt['text'] ?? '') : ($opt->option_text ?? '') }}" placeholder="الخيار {{ $oi + 1 }}" required class="form-input">
                                <input type="hidden" name="questions[{{ $qi }}][options][{{ $oi }}][is_correct]" value="0">
                                <input type="checkbox" name="questions[{{ $qi }}][options][{{ $oi }}][is_correct]" value="1" @checked((is_array($opt) ? ($opt['is_correct'] ?? false) : ($opt->is_correct ?? false))) title="الإجابة الصحيحة">
                                <button type="button" class="text-red-400 hover:text-red-600 remove-option"><i class="fas fa-minus-circle"></i></button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="text-sm text-purple-600 hover:text-purple-800 mt-2 add-option"><i class="fas fa-plus-circle"></i> إضافة خيار</button>
                    </div>
                    @endforeach
                </div>

                <button type="button" id="add-question" class="btn-sm bg-purple-100 text-purple-700 hover:bg-purple-200">
                    <i class="fas fa-plus-circle"></i> إضافة سؤال
                </button>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn-primary">
                    {{ isset($quiz) ? 'تحديث' : 'إنشاء' }}
                </button>
                <a href="{{ route('teacher.quizzes.index', $course ?? $quiz->section->course) }}" class="btn-neutral">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
@push('scripts')
<script>
let questionIndex = {{ count($questions ?? $quiz->questions ?? []) }};

document.getElementById('add-question')?.addEventListener('click', function() {
    const container = document.getElementById('questions-container');
    const div = document.createElement('div');
    div.className = 'question-box border rounded-lg p-4 mb-4 bg-gray-50';
    div.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <span class="font-bold text-purple-700">سؤال #${questionIndex + 1}</span>
            <button type="button" class="text-red-500 hover:text-red-700 remove-question"><i class="fas fa-times"></i></button>
        </div>
        <div class="mb-3">
            <label class="form-label">نص السؤال</label>
            <textarea name="questions[${questionIndex}][content]" rows="2" required class="form-textarea"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-3">
            <div>
                <label class="form-label">النوع</label>
                <select name="questions[${questionIndex}][type]" class="form-select">
                    <option value="multiple_choice">اختيار من متعدد</option>
                    <option value="true_false">صح/خطأ</option>
                    <option value="fill_in_blank">تعبئة فراغ</option>
                </select>
            </div>
            <div>
                <label class="form-label">الدرجة</label>
                <input type="number" name="questions[${questionIndex}][score]" value="1" min="1" class="form-input">
            </div>
        </div>
        <div class="options-container">
            <label class="form-label">الخيارات</label>
            <div class="option-row">
                <input type="radio" name="questions[${questionIndex}][correct]" value="0" checked>
                <input type="text" name="questions[${questionIndex}][options][0][text]" placeholder="الخيار 1" required class="form-input">
                <input type="hidden" name="questions[${questionIndex}][options][0][is_correct]" value="0">
                <input type="checkbox" name="questions[${questionIndex}][options][0][is_correct]" value="1" title="الإجابة الصحيحة">
                <button type="button" class="text-red-400 hover:text-red-600 remove-option"><i class="fas fa-minus-circle"></i></button>
            </div>
            <div class="option-row">
                <input type="radio" name="questions[${questionIndex}][correct]" value="1">
                <input type="text" name="questions[${questionIndex}][options][1][text]" placeholder="الخيار 2" required class="form-input">
                <input type="hidden" name="questions[${questionIndex}][options][1][is_correct]" value="0">
                <input type="checkbox" name="questions[${questionIndex}][options][1][is_correct]" value="1" title="الإجابة الصحيحة">
                <button type="button" class="text-red-400 hover:text-red-600 remove-option"><i class="fas fa-minus-circle"></i></button>
            </div>
        </div>
        <button type="button" class="text-sm text-purple-600 hover:text-purple-800 mt-2 add-option"><i class="fas fa-plus-circle"></i> إضافة خيار</button>
    `;
    container.appendChild(div);
    questionIndex++;
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-question')) {
        e.target.closest('.question-box').remove();
    }
    if (e.target.closest('.remove-option')) {
        const row = e.target.closest('.option-row');
        const container = row.closest('.options-container');
        if (container && container.querySelectorAll('.option-row').length > 2) {
            row.remove();
        }
    }
    if (e.target.closest('.add-option')) {
        const container = e.target.closest('.question-box');
        const qIdx = Array.from(document.querySelectorAll('.question-box')).indexOf(container);
        const optCount = container.querySelectorAll('.option-row').length;
        const row = document.createElement('div');
        row.className = 'option-row';
        row.innerHTML = `
            <input type="radio" name="questions[${qIdx}][correct]" value="${optCount}">
            <input type="text" name="questions[${qIdx}][options][${optCount}][text]" placeholder="الخيار ${optCount + 1}" required class="form-input">
            <input type="hidden" name="questions[${qIdx}][options][${optCount}][is_correct]" value="0">
            <input type="checkbox" name="questions[${qIdx}][options][${optCount}][is_correct]" value="1" title="الإجابة الصحيحة">
            <button type="button" class="text-red-400 hover:text-red-600 remove-option"><i class="fas fa-minus-circle"></i></button>
        `;
        container.querySelector('.options-container').appendChild(row);
    }
});
</script>
@endpush
