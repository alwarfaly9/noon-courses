@extends('layouts.admin')
@section('title', 'الشارات')
@section('content')
<div class="card">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-trophy text-purple-600 mr-3"></i>
            إدارة الشارات
        </h2>
        <button onclick="openModal('addModal')" class="btn-primary">
            <i class="fas fa-plus"></i><span>إضافة شارة</span>
        </button>
    </div>
    <div class="overflow-x-auto">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>النوع</th>
                    <th>المستخدمون</th>
                    <th>نقاط XP</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($badges as $badge)
                <tr>
                    <td>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <i class="{{ $badge->icon ?? 'fas fa-medal' }} text-purple-600"></i>
                            <span class="font-medium">{{ $badge->name }}</span>
                        </div>
                    </td>
                    <td class="text-sm">{{ $badge->type }}</td>
                    <td>
                        <a href="{{ route('admin.achievements.users', $badge) }}" class="text-purple-600 hover:underline">
                            {{ $badge->users_count ?? $badge->users()->count() }} مستخدم
                        </a>
                    </td>
                    <td class="text-sm">{{ $badge->xp_reward ?? 0 }}</td>
                    <td>
                        @if($badge->is_active)
                        <span class="badge-success">نشط</span>
                        @else
                        <span class="badge-neutral">معطل</span>
                        @endif
                    </td>
                    <td class="text-sm space-x-2 space-x-reverse">
                        <button onclick="openEditModal({{ $badge->id }})" class="text-blue-600 hover:text-blue-900"><i class="fas fa-edit"></i></button>
                        <form method="POST" action="{{ url('/admin/achievements/' . $badge->id) }}" class="inline" onsubmit="return confirm('حذف الشارة؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><i class="fas fa-trophy empty-state-icon"></i><p class="empty-state-text">لا توجد شارات</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $badges->links() }}</div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-content max-h-[90vh] overflow-y-auto">
        <div class="modal-header">
            <h3>إضافة شارة جديدة</h3>
        </div>
        <form method="POST" action="{{ url('/admin/achievements') }}">
            @csrf
            <div class="modal-body">
                <div class="mb-4">
                    <label class="form-label">الاسم</label>
                    <input type="text" name="name" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="2" class="form-textarea"></textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label">النوع</label>
                    <select name="type" required class="form-select">
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
                    <label class="form-label">أيقونة (Font Awesome)</label>
                    <input type="text" name="icon" placeholder="fas fa-medal" class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">شرط التفعيل</label>
                    <input type="text" name="condition_type" placeholder="streak_days, courses_completed..." class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">قيمة الشرط</label>
                    <input type="number" name="condition_value" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">نقاط XP</label>
                    <input type="number" name="xp_reward" value="0" class="form-input">
                </div>
                <div class="mb-4">
                    <label class="flex items-center space-x-2 space-x-reverse">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span class="mr-2">نشط</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-primary">إضافة</button>
                <button type="button" onclick="closeModal('addModal')" class="btn-neutral">إلغاء</button>
            </div>
        </form>
    </div>
</div>
<script>
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
</script>
@endsection
