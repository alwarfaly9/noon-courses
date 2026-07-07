@extends('layouts.admin')
@section('title', 'الحملات')
@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-bullhorn text-orange-500 mr-3"></i>
            إدارة الحملات
        </h2>
        <a href="{{ route('admin.campaigns.create') }}" class="btn-primary px-4 py-2 rounded flex items-center space-x-2 space-x-reverse">
            <i class="fas fa-plus"></i><span>حملة جديدة</span>
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-orange-500 to-orange-600 text-white">
                <tr>
                    <th class="px-6 py-4 text-right">الاسم</th>
                    <th class="px-6 py-4 text-right">النوع</th>
                    <th class="px-6 py-4 text-right">المشاركون</th>
                    <th class="px-6 py-4 text-right">المدة</th>
                    <th class="px-6 py-4 text-right">الحالة</th>
                    <th class="px-6 py-4 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($campaigns as $c)
                <tr>
                    <td class="px-6 py-4 font-medium">{{ \Str::limit($c->name, 40) }}</td>
                    <td class="px-6 py-4 text-sm">{{ $c->type }}</td>
                    <td class="px-6 py-4 text-sm">{{ $c->participations_count }}</td>
                    <td class="px-6 py-4 text-sm">
                        {{ $c->starts_at?->format('Y-m-d') ?? '—' }} → {{ $c->ends_at?->format('Y-m-d') ?? '—' }}
                    </td>
                    <td class="px-6 py-4">
                        @if($c->is_active)
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">نشط</span>
                        @else
                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">معطل</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2 space-x-reverse">
                        <a href="{{ route('admin.campaigns.edit', $c) }}" class="text-blue-600 hover:text-blue-900"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('admin.campaigns.destroy', $c) }}" class="inline" onsubmit="return confirm('حذف الحملة؟')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">لا توجد حملات</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $campaigns->links() }}</div>
</div>
@endsection
