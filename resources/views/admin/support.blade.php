@extends('layouts.admin')

@section('title', 'الدعم الفني')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-headset text-green-600 mr-3"></i>
            إدارة تذاكر الدعم
        </h2>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">مفتوحة</div>
            <div class="text-2xl font-bold text-red-600">{{ \App\Models\SupportTicket::where('status', 'open')->count() }}</div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">قيد المعالجة</div>
            <div class="text-2xl font-bold text-yellow-600">{{ \App\Models\SupportTicket::where('status', 'in_progress')->count() }}</div>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">مُحلّة</div>
            <div class="text-2xl font-bold text-green-600">{{ \App\Models\SupportTicket::where('status', 'resolved')->count() }}</div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">إجمالي التذاكر</div>
            <div class="text-2xl font-bold text-blue-600">{{ \App\Models\SupportTicket::count() }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <form method="GET" class="flex items-center space-x-4 space-x-reverse">
            <select name="status" class="px-4 py-2 border border-gray-300 rounded">
                <option value="">جميع الحالات</option>
                <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>مفتوحة</option>
                <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>قيد المعالجة</option>
                <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>محلّة</option>
            </select>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">
                <i class="fas fa-filter"></i> تصفية
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">رقم التذكرة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">المستخدم</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الموضوع</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الأولوية</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">التاريخ</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($tickets as $ticket)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-mono text-sm font-bold text-green-600">{{ $ticket->ticket_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <i class="fas fa-user mr-1"></i> {{ $ticket->user->name }}
                    </td>
                    <td class="px-6 py-4 text-sm">{{ Str::limit($ticket->subject, 40) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($ticket->priority === 'urgent')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-exclamation-circle mr-1"></i> عاجل
                        </span>
                        @elseif($ticket->priority === 'high')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                            <i class="fas fa-arrow-up mr-1"></i> عالية
                        </span>
                        @elseif($ticket->priority === 'medium')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-minus mr-1"></i> متوسطة
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            <i class="fas fa-arrow-down mr-1"></i> منخفضة
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($ticket->status === 'open')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-circle mr-1"></i> مفتوحة
                        </span>
                        @elseif($ticket->status === 'in_progress')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i> قيد المعالجة
                        </span>
                        @elseif($ticket->status === 'resolved')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check mr-1"></i> محلّة
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            <i class="fas fa-times mr-1"></i> مغلقة
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $ticket->created_at->format('Y-m-d') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 space-x-reverse">
                        <button class="text-blue-600 hover:text-blue-900" title="عرض">
                            <i class="fas fa-eye"></i>
                        </button>
                        @if($ticket->status === 'open')
                        <button class="text-green-600 hover:text-green-900" title="تعيين">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-headset text-4xl mb-2"></i>
                        <p>لا توجد تذاكر</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $tickets->links() }}
    </div>
</div>
@endsection

