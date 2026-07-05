@extends('layouts.admin')

@section('title', 'المعاملات')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-exchange-alt text-green-600 mr-3"></i>
            المعاملات المالية
        </h2>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">إجمالي الإيرادات</div>
            <div class="text-2xl font-bold text-green-600">{{ number_format(\App\Models\Transaction::where('status', 'completed')->sum('amount'), 0) }} د.ل</div>
        </div>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">إجمالي المعاملات</div>
            <div class="text-2xl font-bold text-blue-600">{{ \App\Models\Transaction::count() }}</div>
        </div>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">قيد المعالجة</div>
            <div class="text-2xl font-bold text-yellow-600">{{ \App\Models\Transaction::where('status', 'pending')->count() }}</div>
        </div>
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="text-sm text-gray-600 mb-1">مكتملة</div>
            <div class="text-2xl font-bold text-purple-600">{{ \App\Models\Transaction::where('status', 'completed')->count() }}</div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">رقم العملية</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">المستخدم</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">نوع العملية</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">المبلغ</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">التاريخ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono">{{ $transaction->transaction_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <i class="fas fa-user mr-1"></i> {{ $transaction->user->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $transaction->type }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">{{ $transaction->amount }} د.ل</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($transaction->status === 'completed')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> مكتملة
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i> {{ $transaction->status }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>لا توجد معاملات</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $transactions->links() }}
    </div>
</div>
@endsection

