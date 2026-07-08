@extends('layouts.admin')

@section('title', 'الشهادات')

@section('content')
<div class="card card-body">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-certificate text-yellow-500"></i>
            الشهادات الصادرة
        </h2>
        <span class="text-sm text-gray-500">
            إجمالي الشهادات: <strong>{{ $certificates->total() }}</strong>
        </span>
    </div>

    <div class="table-container">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>رقم الشهادة</th>
                    <th>الطالب</th>
                    <th>الدورة</th>
                    <th>تاريخ الإصدار</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $cert)
                <tr>
                    <td>
                        <span class="badge-warning font-mono text-xs">
                            {{ $cert->certificate_id }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="avatar avatar-sm bg-brand-50 text-brand font-bold">
                                {{ mb_substr($cert->user->name ?? '?', 0, 1) }}
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $cert->user->name ?? 'محذوف' }}</div>
                                <div class="text-xs text-gray-400">{{ $cert->user->email ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm font-medium text-gray-900">{{ $cert->course->title ?? 'محذوفة' }}</div>
                    </td>
                    <td>
                        {{ $cert->issued_at ? $cert->issued_at->format('Y/m/d') : '-' }}
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ url('/api/certificates/' . $cert->certificate_id . '/download') }}"
                               target="_blank"
                               class="btn-success btn-sm">
                                <i class="fas fa-download"></i>تحميل
                            </a>
                            <a href="{{ url('/api/certificates/verify/' . $cert->certificate_id) }}"
                               target="_blank"
                               class="btn-secondary btn-sm">
                                <i class="fas fa-check-circle"></i>تحقق
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-certificate text-yellow-500"></i>
                            </div>
                            <div class="empty-state-title">لا توجد شهادات</div>
                            <div class="empty-state-text">لم يتم إصدار أي شهادات بعد</div>
                        </div>
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
