@extends('layouts.admin')

@section('title', 'الدورات')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-book text-green-600 mr-3"></i>
            إدارة الدورات
        </h2>
        <a href="{{ route('admin.courses.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center">
            <i class="fas fa-plus ml-2"></i> إضافة دورة
        </a>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex space-x-8 space-x-reverse">
            <a href="{{ url()->current() }}?status=all" 
               class="py-4 px-1 border-b-2 font-medium text-sm {{ request('status') == 'all' || !request('status') ? 'border-green-600 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-list mr-1"></i> جميع الدورات
            </a>
            <a href="{{ url()->current() }}?status=pending" 
               class="py-4 px-1 border-b-2 font-medium text-sm {{ request('status') == 'pending' ? 'border-green-600 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-clock mr-1"></i> قيد المراجعة
            </a>
            <a href="{{ url()->current() }}?status=published" 
               class="py-4 px-1 border-b-2 font-medium text-sm {{ request('status') == 'published' ? 'border-green-600 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-check-circle mr-1"></i> منشورة
            </a>
        </nav>
    </div>

    <!-- Filters -->
    <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="text" name="search" placeholder="البحث..." value="{{ request('search') }}" 
                   class="flex-1 px-4 py-2 border border-gray-300 rounded">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">العنوان</th>
                    @if(auth()->user()->hasRole('admin'))
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">المحاضر</th>
                    @endif
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">السعر</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الطلاب</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">التقييم</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($courses as $course)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ Str::limit($course->title, 40) }}</div>
                    </td>
                    @if(auth()->user()->hasRole('admin'))
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <i class="fas fa-user mr-1"></i> {{ $course->teacher->name }}
                    </td>
                    @endif
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                        {{ $course->price }} د.ل
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <i class="fas fa-users mr-1 text-gray-400"></i> {{ $course->students_count }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center">
                            <i class="fas fa-star text-yellow-400 mr-1"></i>
                            <span>{{ $course->rating }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($course->status === 'published')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> منشور
                        </span>
                        @elseif($course->status === 'pending')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i> قيد المراجعة
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i> {{ $course->status }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 space-x-reverse">
                        @if($course->status === 'pending' && auth()->user()->hasRole('admin'))
                        <form method="POST" action="{{ route('admin.courses.approve', $course->id) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="text-green-600 hover:text-green-900"
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
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ auth()->user()->hasRole('admin') ? 7 : 6 }}" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-book text-4xl mb-2"></i>
                        <p>لا يوجد دورات</p>
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
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-bold text-gray-800 mb-1">رفض الدورة</h3>
        <p id="rejectCourseName" class="text-sm text-gray-500 mb-4"></p>

        <form id="rejectForm" method="POST">
            @csrf
            <label class="block text-sm font-medium text-gray-700 mb-1">
                سبب الرفض <span class="text-red-500">*</span>
            </label>
            <textarea name="reason" rows="4" required
                      class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-300 text-sm"
                      placeholder="اكتب سبباً واضحاً سيُرسَل للمعلم عبر البريد الإلكتروني..."></textarea>

            <div class="flex justify-end gap-3 mt-4">
                <button type="button" onclick="closeRejectModal()"
                        class="px-4 py-2 border rounded-lg text-gray-600 hover:bg-gray-50">إلغاء</button>
                <button type="submit"
                        class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">
                    تأكيد الرفض
                </button>
            </div>
        </form>
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
