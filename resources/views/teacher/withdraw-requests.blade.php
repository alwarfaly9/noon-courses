@extends('layouts.teacher')
@section('title', 'طلبات السحب')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-yellow-100 text-yellow-600 rounded-full p-3">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">قيد الانتظار</p>
                <p class="text-2xl font-bold">{{ $totalPending }}</p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-green-100 text-green-600 rounded-full p-3">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">إجمالي المدفوع</p>
                <p class="text-2xl font-bold">{{ number_format($totalPaid, 2) }} <span class="text-sm font-normal">د.ل</span></p>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-5 flex items-center gap-4">
            <div class="bg-purple-100 text-purple-600 rounded-full p-3">
                <i class="fas fa-wallet text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">الرصيد المتاح</p>
                <p class="text-2xl font-bold">{{ number_format($availableBalance, 2) }} <span class="text-sm font-normal">د.ل</span></p>
            </div>
        </div>
    </div>

    <!-- New Request Button / Form -->
    <div class="bg-white rounded-xl shadow p-6" x-data="{ open: false }">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold flex items-center gap-2">
                <i class="fas fa-paper-plane text-purple-600"></i> طلب سحب جديد
            </h3>
            <button @click="open = !open"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm">
                <i class="fas fa-plus me-1"></i> طلب سحب
            </button>
        </div>

        <div x-show="open" x-transition class="mt-5 border-t pt-5">
            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                <ul class="list-disc pr-6 space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('teacher.withdraw-requests.store') }}"
                  class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">المبلغ (د.ل) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="1" name="amount"
                           value="{{ old('amount') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم البنك <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_name"
                           value="{{ old('bank_name') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">اسم صاحب الحساب <span class="text-red-500">*</span></label>
                    <input type="text" name="account_name"
                           value="{{ old('account_name') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">رقم الحساب <span class="text-red-500">*</span></label>
                    <input type="text" name="account_number"
                           value="{{ old('account_number') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300" required>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">IBAN (اختياري)</label>
                    <input type="text" name="iban"
                           value="{{ old('iban') }}"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-300">
                </div>

                <div class="md:col-span-2">
                    <button type="submit"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-8 py-2 rounded-lg">
                        إرسال الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">سجل الطلبات</h3>
        </div>

        @if($requests->count())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المبلغ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">البنك</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">رقم الحساب</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">التاريخ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($requests as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $req->id }}</td>
                        <td class="px-6 py-4 text-sm font-semibold">{{ number_format($req->amount, 2) }} د.ل</td>
                        <td class="px-6 py-4 text-sm">{{ $req->bank_name }}</td>
                        <td class="px-6 py-4 text-sm font-mono">{{ $req->account_number }}</td>
                        <td class="px-6 py-4">
                            @if($req->status === 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">قيد الانتظار</span>
                            @elseif($req->status === 'paid')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">مدفوع</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800"
                                      title="{{ $req->rejection_reason }}">مرفوض</span>
                                @if($req->rejection_reason)
                                <p class="text-xs text-red-500 mt-1">{{ $req->rejection_reason }}</p>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $req->created_at->format('Y/m/d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">
            {{ $requests->links() }}
        </div>
        @else
        <div class="px-6 py-12 text-center text-gray-400">
            <i class="fas fa-wallet text-4xl mb-3 block"></i>
            لا توجد طلبات سحب بعد.
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endpush
