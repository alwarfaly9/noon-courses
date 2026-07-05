@extends('layouts.admin')

@section('title', isset($user) ? 'تعديل المستخدم' : 'إضافة مستخدم جديد')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center space-x-2 space-x-reverse mb-6">
        <a href="{{ route('admin.users') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-right"></i>
        </a>
        <h2 class="text-2xl font-bold text-gray-800">
            {{ isset($user) ? 'تعديل: '.$user->name : 'إضافة مستخدم جديد' }}
        </h2>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong class="font-bold">خطأ!</strong>
                <ul class="list-disc list-inside mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ isset($user) ? route('admin.users.update', $user->id) : route('admin.users.store') }}"
              method="POST">
            @csrf
            @if(isset($user)) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">الاسم الكامل</label>
                    <input type="text" name="name"
                           value="{{ old('name', $user->name ?? '') }}" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">البريد الإلكتروني</label>
                    <input type="email" name="email"
                           value="{{ old('email', $user->email ?? '') }}" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        كلمة المرور
                        @if(isset($user))
                        <span class="text-gray-400 font-normal text-xs">(اتركها فارغة للإبقاء على الحالية)</span>
                        @endif
                    </label>
                    <input type="password" name="password"
                           {{ isset($user) ? '' : 'required' }}
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <!-- Role -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">الدور</label>
                    @php $currentRole = isset($user) ? ($user->roles->first()->name ?? 'student') : old('role', 'student'); @endphp
                    <select name="role" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="student"  {{ $currentRole === 'student'  ? 'selected' : '' }}>طالب (Student)</option>
                        <option value="teacher"  {{ $currentRole === 'teacher'  ? 'selected' : '' }}>معلم (Teacher)</option>
                        <option value="admin"    {{ $currentRole === 'admin'    ? 'selected' : '' }}>مسؤول (Admin)</option>
                    </select>
                </div>

                <!-- Phone -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">رقم الهاتف (اختياري)</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $user->phone ?? '') }}"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('admin.users') }}"
                   class="text-gray-600 hover:text-gray-800 flex items-center gap-1">
                    <i class="fas fa-times"></i> إلغاء
                </a>
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline flex items-center">
                    <i class="fas fa-save ml-2"></i>
                    {{ isset($user) ? 'حفظ التعديلات' : 'حفظ المستخدم' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
