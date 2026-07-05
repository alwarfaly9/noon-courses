@extends('layouts.teacher')
@section('title', 'دوراتي')
@section('content')
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center gap-2">
            <i class="fas fa-graduation-cap text-purple-600"></i> دوراتي
        </h2>
        <a href="{{ route('teacher.courses.create') }}"
           class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="fas fa-plus"></i> إضافة دورة
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gradient-to-r from-purple-600 to-purple-700 text-white">
                <tr>
                    <th class="px-4 py-4 text-right font-semibold">الدورة</th>
                    <th class="px-4 py-4 text-right font-semibold">التصنيف</th>
                    <th class="px-4 py-4 text-right font-semibold">السعر</th>
                    <th class="px-4 py-4 text-right font-semibold">الطلاب</th>
                    <th class="px-4 py-4 text-right font-semibold">الحالة</th>
                    <th class="px-4 py-4 text-right font-semibold">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($courses as $course)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            @if($course->image)
                            <img src="{{ asset('storage/'.$course->image) }}"
                                 class="w-12 h-12 rounded-lg object-cover" alt="">
                            @else
                            <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-book text-purple-400"></i>
                            </div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900">{{ \Str::limit($course->title, 50) }}</div>
                                <div class="text-xs text-gray-400">{{ $course->level }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-gray-600">{{ $course->category->name ?? '—' }}</td>
                    <td class="px-4 py-4 font-semibold text-purple-600">
                        {{ $course->price > 0 ? $course->price.' د.ل' : 'مجاني' }}
                    </td>
                    <td class="px-4 py-4 text-center">
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full">
                            {{ $course->students_count ?? 0 }}
                        </span>
                    </td>
                    <td class="px-4 py-4">
                        @if($course->status === 'published')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle ml-1"></i> منشور
                        </span>
                        @elseif($course->status === 'pending')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-hourglass-half ml-1"></i> قيد المراجعة
                        </span>
                        @elseif($course->status === 'rejected')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-times-circle ml-1"></i> مرفوض
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">{{ $course->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex gap-2 flex-wrap">
                            <a href="{{ route('teacher.courses.content', $course->id) }}"
                               class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1.5 rounded-lg">
                                <i class="fas fa-list"></i> المحتوى
                            </a>
                            <a href="{{ route('teacher.courses.edit', $course->id) }}"
                               class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs px-3 py-1.5 rounded-lg">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <form method="POST" action="{{ route('teacher.courses.destroy', $course->id) }}"
                                  onsubmit="return confirm('هل أنت متأكد من حذف الدورة؟')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1.5 rounded-lg">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <i class="fas fa-inbox text-4xl block mb-3"></i>
                        لا توجد دورات —
                        <a href="{{ route('teacher.courses.create') }}" class="text-purple-600 underline">أضف أولى دوراتك</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $courses->links() }}</div>
</div>
@endsection
