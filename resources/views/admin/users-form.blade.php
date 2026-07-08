@extends('layouts.admin')

@section('title', isset($user) ? 'تعديل المستخدم' : 'إضافة مستخدم جديد')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="page-header">
        <a href="{{ route('admin.users') }}" class="text-gray-500 hover:text-gray-700 ml-2">
            <i class="fas fa-arrow-right"></i>
        </a>
        <h2 class="page-title">
            {{ isset($user) ? 'تعديل: '.$user->name : 'إضافة مستخدم جديد' }}
        </h2>
    </div>

    <div class="card">
        @if($errors->any())
            <div class="alert-danger mb-4">
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
                    <label class="form-label">الاسم الكامل</label>
                    <input type="text" name="name"
                           value="{{ old('name', $user->name ?? '') }}" required
                           class="form-input w-full">
                </div>

                <!-- Email -->
                <div class="mb-4">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email"
                           value="{{ old('email', $user->email ?? '') }}" required
                           class="form-input w-full">
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label class="form-label">
                        كلمة المرور
                        @if(isset($user))
                        <span class="text-gray-400 font-normal text-xs">(اتركها فارغة للإبقاء على الحالية)</span>
                        @endif
                    </label>
                    <input type="password" name="password"
                           {{ isset($user) ? '' : 'required' }}
                           class="form-input w-full">
                </div>

                <!-- Role -->
                <div class="mb-4">
                    <label class="form-label">الدور</label>
                    @php $currentRole = isset($user) ? ($user->roles->first()->name ?? 'student') : old('role', 'student'); @endphp
                    <select name="role" required class="form-select w-full">
                        <option value="student"  {{ $currentRole === 'student'  ? 'selected' : '' }}>طالب (Student)</option>
                        <option value="teacher"  {{ $currentRole === 'teacher'  ? 'selected' : '' }}>معلم (Teacher)</option>
                        <option value="admin"    {{ $currentRole === 'admin'    ? 'selected' : '' }}>مسؤول (Admin)</option>
                    </select>
                </div>

                <!-- Phone -->
                <div class="mb-4">
                    <label class="form-label">رقم الهاتف (اختياري)</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $user->phone ?? '') }}"
                           class="form-input w-full">
                </div>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <a href="{{ route('admin.users') }}"
                   class="btn-secondary flex items-center gap-1">
                    <i class="fas fa-times"></i> إلغاء
                </a>
                <button type="submit"
                        class="btn-primary flex items-center">
                    <i class="fas fa-save ml-2"></i>
                    {{ isset($user) ? 'حفظ التعديلات' : 'حفظ المستخدم' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
