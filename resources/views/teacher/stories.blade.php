@extends('layouts.teacher')
@section('title', 'القصص')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-story text-purple-600 mr-3"></i>
            قصصي
        </h2>
        <a href="{{ route('teacher.stories.create') }}" class="btn-primary px-4 py-2 rounded flex items-center space-x-2 space-x-reverse">
            <i class="fas fa-plus"></i><span>قصة جديدة</span>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-purple-600 to-purple-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right">العنوان</th>
                    <th class="px-6 py-4 text-right">الدورة</th>
                    <th class="px-6 py-4 text-right">المشاهدات</th>
                    <th class="px-6 py-4 text-right">تاريخ النشر</th>
                    <th class="px-6 py-4 text-right">الحالة</th>
                    <th class="px-6 py-4 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($stories as $story)
                <tr>
                    <td class="px-6 py-4 font-medium">{{ \Str::limit($story->title, 40) }}</td>
                    <td class="px-6 py-4 text-sm">{{ $story->course->title ?? 'عام' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $story->views_count }}</td>
                    <td class="px-6 py-4 text-sm">{{ $story->created_at->format('Y-m-d') }}</td>
                    <td class="px-6 py-4">
                        @if($story->is_active)
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">منشور</span>
                        @else
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">مسودة</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2 space-x-reverse">
                        <a href="{{ route('teacher.stories.edit', $story) }}" class="text-blue-600 hover:text-blue-900"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('teacher.stories.destroy', $story) }}" class="inline" onsubmit="return confirm('حذف القصة؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد قصص بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $stories->links() }}</div>
</div>
@endsection
