@extends('layouts.admin')
@section('title', 'الشارات')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-trophy text-purple-600 mr-3"></i>
            إدارة الشارات
        </h2>
        <button onclick="openModal('addModal')" class="btn-primary px-4 py-2 rounded flex items-center space-x-2 space-x-reverse">
            <i class="fas fa-plus"></i><span>إضافة شارة</span>
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-purple-600 to-purple-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right">الاسم</th>
                    <th class="px-6 py-4 text-right">النوع</th>
                    <th class="px-6 py-4 text-right">المستخدمون</th>
                    <th class="px-6 py-4 text-right">نقاط XP</th>
                    <th class="px-6 py-4 text-right">الحالة</th>
                    <th class="px-6 py-4 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($badges as $badge)
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <i class="{{ $badge->icon ?? 'fas fa-medal' }} text-purple-600"></i>
                            <span class="font-medium">{{ $badge->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $badge->type }}</td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.achievements.users', $badge) }}" class="text-purple-600 hover:underline">
                            {{ $badge->users_count ?? $badge->users()->count() }} مستخدم
                        </a>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $badge->xp_reward ?? 0 }}</td>
                    <td class="px-6 py-4">
                        @if($badge->is_active)
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">نشط</span>
                        @else
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">معطل</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2 space-x-reverse">
                        <button onclick="openEditModal({{ $badge->id }})" class="text-blue-600 hover:text-blue-900"><i class="fas fa-edit"></i></button>
                        <form method="POST" action="{{ url('/admin/achievements/' . $badge->id) }}" class="inline" onsubmit="return confirm('حذف الشارة؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد شارات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $badges->links() }}</div>
</div>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-8 w-96 max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6">إضافة شارة جديدة</h3>
        <form method="POST" action="{{ url('/admin/achievements') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الاسم</label>
                <input type="text" name="name" required class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الوصف</label>
                <textarea name="description" rows="2" class="shadow border rounded w-full py-2 px-3"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">النوع</label>
                <select name="type" required class="shadow border rounded w-full py-2 px-3">
                    <option value="lesson">درس</option>
                    <option value="course">دورة</option>
                    <option value="streak">استمرارية</option>
                    <option value="quiz">اختبار</option>
                    <option value="path">مسار</option>
                    <option value="level">مستوى</option>
                    <option value="special">خاص</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">أيقونة (Font Awesome)</label>
                <input type="text" name="icon" placeholder="fas fa-medal" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">شرط التفعيل</label>
                <input type="text" name="condition_type" placeholder="streak_days, courses_completed..." class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">قيمة الشرط</label>
                <input type="number" name="condition_value" required class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">نقاط XP</label>
                <input type="number" name="xp_reward" value="0" class="shadow border rounded w-full py-2 px-3">
            </div>
            <div class="mb-4">
                <label class="flex items-center space-x-2 space-x-reverse">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span class="mr-2">نشط</span>
                </label>
            </div>
            <div class="flex space-x-4 space-x-reverse">
                <button type="submit" class="flex-1 btn-primary font-bold py-2 px-4 rounded">إضافة</button>
                <button type="button" onclick="closeModal('addModal')" class="flex-1 bg-gray-300 hover:bg-gray-400 font-bold py-2 px-4 rounded">إلغاء</button>
            </div>
        </form>
    </div>
</div>
<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
</script>
@endsection
