@extends('layouts.teacher')
@section('title', 'لوحة التحكم')
@section('content')
<div class="space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">
        مرحباً، {{ Auth::user()->name }} 👋
    </h2>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-purple-500">
            <div class="text-sm text-gray-500 mb-1">إجمالي الدورات</div>
            <div class="text-3xl font-bold text-purple-600">{{ $totalCourses }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-green-500">
            <div class="text-sm text-gray-500 mb-1">دورات منشورة</div>
            <div class="text-3xl font-bold text-green-600">{{ $publishedCourses }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-blue-500">
            <div class="text-sm text-gray-500 mb-1">إجمالي الطلاب</div>
            <div class="text-3xl font-bold text-blue-600">{{ $totalStudents }}</div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 border-r-4 border-orange-500">
            <div class="text-sm text-gray-500 mb-1">أرباحك الكلية</div>
            <div class="text-3xl font-bold text-orange-600">{{ number_format($totalEarnings, 0) }} د.ل</div>
        </div>
    </div>

    @if($pendingCourses > 0)
    <div class="bg-yellow-50 border border-yellow-300 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-hourglass-half text-yellow-500 text-xl"></i>
        <span class="text-yellow-800">{{ $pendingCourses }} دورة قيد مراجعة الإدارة — ستُنشر بعد الموافقة.</span>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="flex gap-3 flex-wrap">
        <a href="{{ route('teacher.courses.create') }}"
           class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2">
            <i class="fas fa-plus"></i> إضافة دورة جديدة
        </a>
        <a href="{{ route('teacher.withdraw-requests') }}"
           class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2">
            <i class="fas fa-money-bill-wave"></i> طلب سحب الأرباح
        </a>
    </div>

    <!-- Recent Enrollments -->
    @if($recentEnrollments->count())
    <div class="bg-white rounded-xl shadow p-5">
        <h3 class="text-lg font-bold mb-4 text-gray-700">
            <i class="fas fa-user-graduate text-purple-500 ml-2"></i>
            آخر الاشتراكات
        </h3>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">الطالب</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">الدورة</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($recentEnrollments as $enrollment)
                <tr>
                    <td class="px-4 py-3">{{ $enrollment->student->name ?? '—' }}</td>
                    <td class="px-4 py-3">{{ \Str::limit($enrollment->course->title ?? '—', 50) }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $enrollment->created_at->format('Y-m-d') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
