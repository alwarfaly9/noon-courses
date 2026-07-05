@extends('layouts.admin')

@section('title', 'المستخدمين')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-users text-green-600 mr-3"></i>
            إدارة المستخدمين
        </h2>
        <a href="{{ route('admin.users.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center space-x-2 space-x-reverse btn-primary">
            <i class="fas fa-plus"></i>
            <span>إضافة مستخدم</span>
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <input type="text" name="search" placeholder="البحث..." value="{{ request('search') }}" 
                   class="flex-1 px-4 py-2 border border-gray-300 rounded">
            <select name="role" class="px-4 py-2 border border-gray-300 rounded">
                <option value="">جميع الأدوار</option>
                <option value="admin">Admin</option>
                <option value="teacher">Teacher</option>
                <option value="student">Student</option>
            </select>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الاسم</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">البريد الإلكتروني</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الدور</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">التاريخ</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-green-600"></i>
                            </div>
                            <div class="mr-4">
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @foreach($user->roles as $role)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                            {{ $role->name === 'admin' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $role->name === 'teacher' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $role->name === 'student' ? 'bg-green-100 text-green-800' : '' }}">
                            <i class="fas fa-{{ $role->name === 'admin' ? 'shield-alt' : ($role->name === 'teacher' ? 'chalkboard-teacher' : 'graduation-cap') }}"></i>
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->is_active)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> نشط
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i> معطل
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $user->created_at->format('Y-m-d') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 space-x-reverse">
                        <!-- Edit -->
                        <a href="{{ route('admin.users.edit', $user->id) }}"
                           class="inline-flex items-center text-blue-600 hover:text-blue-900 gap-1">
                            <i class="fas fa-edit"></i>
                        </a>

                        <!-- Toggle Active -->
                        <form method="POST" action="{{ route('admin.users.toggle-active', $user->id) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="{{ $user->is_active ? 'text-orange-500 hover:text-orange-700' : 'text-green-600 hover:text-green-800' }}"
                                    title="{{ $user->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}"
                                    onclick="return confirm('{{ $user->is_active ? 'تعطيل' : 'تفعيل' }} حساب {{ addslashes($user->name) }}؟')">
                                <i class="fas fa-{{ $user->is_active ? 'ban' : 'check-circle' }}"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>لا يوجد مستخدمين</p>
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
