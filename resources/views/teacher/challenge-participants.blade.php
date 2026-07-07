@extends('layouts.teacher')
@section('title', 'مشاركو التحدي')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="mb-6">
        <a href="{{ route('teacher.challenges.index') }}" class="text-purple-600 hover:underline">
            <i class="fas fa-arrow-right ml-1"></i> عودة للتحديات
        </a>
        <h2 class="text-2xl font-bold mt-2 flex items-center">
            <i class="fas fa-users text-orange-500 mr-3"></i>
            مشاركو: {{ $challenge->title }}
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                <tr>
                    <th class="px-6 py-4 text-right">المستخدم</th>
                    <th class="px-6 py-4 text-right">تاريخ الانضمام</th>
                    <th class="px-6 py-4 text-right">التقدم</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($participants as $p)
                <tr>
                    <td class="px-6 py-4">{{ $p->user->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $p->created_at->format('Y-m-d') }}</td>
                    <td class="px-6 py-4 text-sm">{{ $p->progress ?? 0 }} / {{ $challenge->goal_value }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">لا يوجد مشاركون بعد</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $participants->links() }}</div>
</div>
@endsection
