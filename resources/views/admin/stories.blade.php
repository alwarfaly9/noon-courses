@extends('layouts.admin')
@section('title', 'القصص')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold mb-6 flex items-center">
        <i class="fas fa-story text-purple-600 mr-3"></i>
        إدارة القصص
    </h2>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-purple-600 to-purple-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right">العنوان</th>
                    <th class="px-6 py-4 text-right">المعلم</th>
                    <th class="px-6 py-4 text-right">الدورة</th>
                    <th class="px-6 py-4 text-right">المشاهدات</th>
                    <th class="px-6 py-4 text-right">الحالة</th>
                    <th class="px-6 py-4 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($stories as $story)
                <tr>
                    <td class="px-6 py-4 font-medium">{{ \Str::limit($story->title, 40) }}</td>
                    <td class="px-6 py-4 text-sm">{{ $story->user->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $story->course->title ?? 'عام' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $story->views_count }}</td>
                    <td class="px-6 py-4">
                        @if($story->is_active)
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">نشط</span>
                        @else
                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">معطل</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2 space-x-reverse">
                        <form method="POST" action="{{ url('/admin/stories/' . $story->id . '/toggle') }}" class="inline">
                            @csrf
                            <button class="text-{{ $story->is_active ? 'red' : 'green' }}-600 hover:underline">
                                {{ $story->is_active ? 'تعطيل' : 'تفعيل' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ url('/admin/stories/' . $story->id) }}" class="inline" onsubmit="return confirm('حذف القصة؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-900 mr-2"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد قصص</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $stories->links() }}</div>
</div>
@endsection
