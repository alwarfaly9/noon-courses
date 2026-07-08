@extends('layouts.admin')

@section('title', 'الدورات')

@section('content')
<div class="card card-body">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-book"></i>
            إدارة الدورات
        </h2>
        <a href="{{ route('admin.courses.create') }}" class="btn-primary">
            <i class="fas fa-plus ml-2"></i> إضافة دورة
        </a>
    </div>

    <!-- Tabs -->
    <div class="nav-tabs">
        <a href="{{ url()->current() }}?status=all"
           class="nav-tab {{ request('status') == 'all' || !request('status') ? 'active' : '' }}">
            <i class="fas fa-list ml-1"></i> جميع الدورات
        </a>
        <a href="{{ url()->current() }}?status=pending"
           class="nav-tab {{ request('status') == 'pending' ? 'active' : '' }}">
            <i class="fas fa-clock ml-1"></i> قيد المراجعة
        </a>
        <a href="{{ url()->current() }}?status=published"
           class="nav-tab {{ request('status') == 'published' ? 'active' : '' }}">
            <i class="fas fa-check-circle ml-1"></i> منشورة
        </a>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="text" name="search" placeholder="البحث..." value="{{ request('search') }}"
                   class="form-input flex-1">
            <button type="submit" class="btn-primary">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="table-container">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>العنوان</th>
                    @if(auth()->user()->hasRole('admin'))
                    <th>المحاضر</th>
                    @endif
                    <th>السعر</th>
                    <th>الطلاب</th>
                    <th>التقييم</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr>
                    <td>
                        <div class="text-sm font-medium text-gray-900">{{ Str::limit($course->title, 40) }}</div>
                    </td>
                    @if(auth()->user()->hasRole('admin'))
                    <td>
                        <i class="fas fa-user ml-1"></i> {{ $course->teacher->name }}
                    </td>
                    @endif
                    <td class="font-semibold text-emerald-600">
                        {{ $course->price }} د.ل
                    </td>
                    <td>
                        <i class="fas fa-users ml-1 text-gray-400"></i> {{ $course->students_count }}
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i>
                            <span>{{ $course->rating }}</span>
                        </div>
                    </td>
                    <td>
                        @if($course->status === 'published')
                        <span class="badge-success">
                            <i class="fas fa-check-circle"></i> منشور
                        </span>
                        @elseif($course->status === 'pending')
                        <span class="badge-warning">
                            <i class="fas fa-clock"></i> قيد المراجعة
                        </span>
                        @else
                        <span class="badge-danger">
                            <i class="fas fa-times-circle"></i> {{ $course->status }}
                        </span>
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-2">
                            @if($course->status === 'pending' && auth()->user()->hasRole('admin'))
                            <form method="POST" action="{{ route('admin.courses.approve', $course->id) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="text-emerald-600 hover:text-emerald-900"
                                        title="موافقة على النشر"
                                        onclick="return confirm('نشر دورة: {{ addslashes($course->title) }}؟')">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </form>
                            <button type="button"
                                    class="text-red-600 hover:text-red-900"
                                    title="رفض الدورة"
                                    onclick="openRejectModal({{ $course->id }}, '{{ addslashes($course->title) }}')">
                                <i class="fas fa-times-circle"></i>
                            </button>
                            @endif
                            <a href="{{ route('admin.courses.edit', $course->id) }}" class="text-orange-600 hover:text-orange-900" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->hasRole('admin') ? 7 : 6 }}">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="empty-state-title">لا يوجد دورات</div>
                            <div class="empty-state-text">لم يتم إضافة أي دورات بعد</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $courses->links() }}
    </div>
</div>

<!-- Reject Course Modal -->
<div id="rejectModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-bold text-gray-800">رفض الدورة</h3>
            <button type="button" onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p id="rejectCourseName" class="text-sm text-gray-500 mb-4"></p>

            <form id="rejectForm" method="POST">
                @csrf
                <label class="form-label">
                    سبب الرفض <span class="text-red-500">*</span>
                </label>
                <textarea name="reason" rows="4" required
                          class="form-textarea"
                          placeholder="اكتب سبباً واضحاً سيُرسَل للمعلم عبر البريد الإلكتروني..."></textarea>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeRejectModal()"
                            class="btn-secondary">إلغاء</button>
                    <button type="submit"
                            class="btn-danger">
                        تأكيد الرفض
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openRejectModal(courseId, courseTitle) {
    document.getElementById('rejectForm').action = '/admin/courses/' + courseId + '/reject';
    document.getElementById('rejectCourseName').textContent = courseTitle;
    document.getElementById('rejectModal').classList.remove('hidden');
}
function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectForm').querySelector('textarea').value = '';
}
// Close on backdrop click
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endpush
