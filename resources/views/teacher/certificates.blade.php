@extends('layouts.teacher')
@section('title', 'الشهادات')
@section('content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
        <i class="fas fa-certificate text-cyan-600"></i>
        الشهادات المصدرة لدوراتك
    </h2>

    @if($certificates->count())
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">الطالب</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">الدورة</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">تاريخ الإصدار</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">رابط التحقق</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($certificates as $cert)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $cert->user->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ \Str::limit($cert->course->title ?? '—', 50) }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $cert->issued_at?->format('Y-m-d') ?? $cert->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('certificates.verify', $cert->certificate_id) }}"
                           target="_blank"
                           class="text-cyan-600 hover:text-cyan-800 underline text-xs">
                            <i class="fas fa-external-link-alt ml-1"></i>
                            تحقق
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $certificates->links() }}
    </div>
    @else
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">
        <i class="fas fa-certificate text-4xl text-gray-300 mb-3"></i>
        <p>لا توجد شهادات مصدرة بعد</p>
        <p class="text-sm mt-1">عند إكمال الطلاب لدوراتك سيتم إصدار الشهادات هنا</p>
    </div>
    @endif
</div>
@endsection
