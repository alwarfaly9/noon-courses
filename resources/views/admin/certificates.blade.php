@extends('layouts.admin')

@section('title', 'الشهادات')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-certificate text-yellow-500 mr-3"></i>
            الشهادات الصادرة
        </h2>
        <span class="text-sm text-gray-500">
            إجمالي الشهادات: <strong>{{ $certificates->total() }}</strong>
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-yellow-500 to-yellow-600 text-white">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">رقم الشهادة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الطالب</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الدورة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">تاريخ الإصدار</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">إجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($certificates as $cert)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-mono text-xs font-bold text-yellow-600 bg-yellow-50 px-2 py-1 rounded">
                            {{ $cert->certificate_id }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center ml-3">
                                <span class="text-green-700 font-bold text-sm">{{ mb_substr($cert->user->name ?? '?', 0, 1) }}</span>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $cert->user->name ?? 'محذوف' }}</div>
                                <div class="text-xs text-gray-400">{{ $cert->user->email ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $cert->course->title ?? 'محذوفة' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $cert->issued_at ? $cert->issued_at->format('Y/m/d') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex gap-2">
                            <a href="{{ url('/api/certificates/' . $cert->certificate_id . '/download') }}"
                               target="_blank"
                               class="text-xs bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1 rounded font-medium">
                                <i class="fas fa-download ml-1"></i>تحميل
                            </a>
                            <a href="{{ url('/api/certificates/verify/' . $cert->certificate_id) }}"
                               target="_blank"
                               class="text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-1 rounded font-medium">
                                <i class="fas fa-check-circle ml-1"></i>تحقق
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-gray-400">
                        <i class="fas fa-certificate text-4xl mb-3 block"></i>
                        لا توجد شهادات صادرة بعد
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($certificates->hasPages())
    <div class="mt-6">
        {{ $certificates->links() }}
    </div>
    @endif
</div>
@endsection
