@extends('layouts.admin')
@section('title', 'مستخدمو الشارة')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="mb-6">
        <a href="{{ url('/admin/achievements') }}" class="text-purple-600 hover:underline">
            <i class="fas fa-arrow-right ml-1"></i> عودة للشارات
        </a>
        <h2 class="text-2xl font-bold mt-2 flex items-center">
            <i class="fas fa-trophy text-purple-600 mr-3"></i>
            مستخدمو شارة: {{ $badge->name }}
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-purple-600 to-purple-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right">المستخدم</th>
                    <th class="px-6 py-4 text-right">تاريخ الحصول</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($users as $ub)
                <tr>
                    <td class="px-6 py-4">{{ $ub->user->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $ub->earned_at?->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="px-6 py-8 text-center text-gray-500">لا يوجد مستخدمون حصلوا على هذه الشارة</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</div>
@endsection
