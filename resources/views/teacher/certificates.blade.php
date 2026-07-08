@extends('layouts.teacher')
@section('title', 'الشهادات')
@section('content')
<div class="space-y-6">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-certificate text-purple-600"></i>
            الشهادات المصدرة لدوراتك
        </h2>
    </div>

    @if($certificates->count())
    <div class="card">
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>الطالب</th>
                        <th>الدورة</th>
                        <th>تاريخ الإصدار</th>
                        <th>رابط التحقق</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($certificates as $cert)
                    <tr>
                        <td>{{ $cert->user->name ?? '—' }}</td>
                        <td>{{ \Str::limit($cert->course->title ?? '—', 50) }}</td>
                        <td class="text-gray-400">{{ $cert->issued_at?->format('Y-m-d') ?? $cert->created_at->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('certificates.verify', $cert->certificate_id) }}"
                               target="_blank"
                               class="text-purple-600 hover:text-purple-800 underline text-xs">
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
    </div>
    @else
    <div class="empty-state">
        <i class="empty-state-icon fas fa-certificate"></i>
        <span class="empty-state-text">لا توجد شهادات مصدرة بعد</span>
        <p class="text-sm mt-1">عند إكمال الطلاب لدوراتك سيتم إصدار الشهادات هنا</p>
    </div>
    @endif
</div>
@endsection
