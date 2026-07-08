@extends('layouts.admin')

@section('title', 'المستخدمين')

@section('content')
<div class="card card-body">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-users"></i>
            إدارة المستخدمين
        </h2>
        <a href="{{ route('admin.users.create') }}" class="btn-primary">
            <i class="fas fa-plus"></i>
            <span>إضافة مستخدم</span>
        </a>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <input type="text" name="search" placeholder="البحث..." value="{{ request('search') }}"
                   class="form-input flex-1">
            <select name="role" class="form-select w-40">
                <option value="">جميع الأدوار</option>
                <option value="admin">Admin</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
            </select>
            <button type="submit" class="btn-primary">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="table-container">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>البريد الإلكتروني</th>
                    <th>الدور</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="avatar bg-brand-50 text-brand">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <div class="flex gap-1">
                            @foreach($user->roles as $role)
                            <span class="
                                {{ $role->name === 'admin' ? 'badge-purple' : '' }}
                                {{ $role->name === 'teacher' ? 'badge-info' : '' }}
                                {{ $role->name === 'student' ? 'badge-success' : '' }}">
                                <i class="fas fa-{{ $role->name === 'admin' ? 'shield-alt' : ($role->name === 'teacher' ? 'chalkboard-teacher' : 'graduation-cap') }}"></i>
                                {{ $role->name }}
                            </span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        @if($user->is_active)
                        <span class="badge-success">
                            <i class="fas fa-check-circle"></i> نشط
                        </span>
                        @else
                        <span class="badge-danger">
                            <i class="fas fa-times-circle"></i> معطل
                        </span>
                        @endif
                    </td>
                    <td>
                        {{ $user->created_at->format('Y-m-d') }}
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <!-- Edit -->
                            <a href="{{ route('admin.users.edit', $user->id) }}"
                               class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-edit"></i>
                            </a>

                            <!-- Toggle Active -->
                            <form method="POST" action="{{ route('admin.users.toggle-active', $user->id) }}" class="inline">
                                @csrf
                                <button type="submit"
                                        class="{{ $user->is_active ? 'text-orange-500 hover:text-orange-700' : 'text-emerald-600 hover:text-emerald-800' }}"
                                        title="{{ $user->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}"
                                        onclick="return confirm('{{ $user->is_active ? 'تعطيل' : 'تفعيل' }} حساب {{ addslashes($user->name) }}؟')">
                                    <i class="fas fa-{{ $user->is_active ? 'ban' : 'check-circle' }}"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="empty-state-title">لا يوجد مستخدمين</div>
                            <div class="empty-state-text">لم يتم إضافة أي مستخدمين بعد</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</div>
@endsection
