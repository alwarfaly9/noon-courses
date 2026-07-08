@extends('layouts.teacher')
@section('title', 'طلبات السحب')

@section('content')
<div class="space-y-6">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-wallet text-purple-600"></i> طلبات السحب
        </h2>
    </div>

    @if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="bg-yellow-100 text-yellow-600 rounded-full p-3">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">قيد الانتظار</p>
                    <p class="text-2xl font-bold">{{ $totalPending }}</p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="bg-green-100 text-green-600 rounded-full p-3">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">إجمالي المدفوع</p>
                    <p class="text-2xl font-bold">{{ number_format($totalPaid, 2) }} <span class="text-sm font-normal">د.ل</span></p>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body flex items-center gap-4">
                <div class="bg-purple-100 text-purple-600 rounded-full p-3">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">الرصيد المتاح</p>
                    <p class="text-2xl font-bold">{{ number_format($availableBalance, 2) }} <span class="text-sm font-normal">د.ل</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- New Request Button / Form -->
    <div class="card" x-data="{ open: false }">
        <div class="card-header">
            <i class="fas fa-paper-plane text-purple-600"></i> طلب سحب جديد
            <button @click="open = !open"
                    class="btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> طلب سحب
            </button>
        </div>

        <div x-show="open" x-transition class="card-body">
            @if($errors->any())
            <div class="alert-danger mb-4">
                <ul class="list-disc pr-6 space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('teacher.withdraw-requests.store') }}"
                  class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf

                <div>
                    <label class="form-label">المبلغ (د.ل) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="1" name="amount"
                           value="{{ old('amount') }}"
                           class="form-input" required>
                </div>

                <div>
                    <label class="form-label">اسم البنك <span class="text-red-500">*</span></label>
                    <input type="text" name="bank_name"
                           value="{{ old('bank_name') }}"
                           class="form-input" required>
                </div>

                <div>
                    <label class="form-label">اسم صاحب الحساب <span class="text-red-500">*</span></label>
                    <input type="text" name="account_name"
                           value="{{ old('account_name') }}"
                           class="form-input" required>
                </div>

                <div>
                    <label class="form-label">رقم الحساب <span class="text-red-500">*</span></label>
                    <input type="text" name="account_number"
                           value="{{ old('account_number') }}"
                           class="form-input" required>
                </div>

                <div class="md:col-span-2">
                    <label class="form-label">IBAN (اختياري)</label>
                    <input type="text" name="iban"
                           value="{{ old('iban') }}"
                           class="form-input">
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="btn-primary">
                        إرسال الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-history text-purple-600"></i> سجل الطلبات
        </div>

        @if($requests->count())
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المبلغ</th>
                        <th>البنك</th>
                        <th>رقم الحساب</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                    <tr>
                        <td class="text-sm text-gray-500">{{ $req->id }}</td>
                        <td class="text-sm font-semibold">{{ number_format($req->amount, 2) }} د.ل</td>
                        <td class="text-sm">{{ $req->bank_name }}</td>
                        <td class="text-sm font-mono">{{ $req->account_number }}</td>
                        <td>
                            @if($req->status === 'pending')
                                <span class="badge-warning">قيد الانتظار</span>
                            @elseif($req->status === 'paid')
                                <span class="badge-success">مدفوع</span>
                            @else
                                <span class="badge-danger"
                                      title="{{ $req->rejection_reason }}">مرفوض</span>
                                @if($req->rejection_reason)
                                <p class="text-xs text-red-500 mt-1">{{ $req->rejection_reason }}</p>
                                @endif
                            @endif
                        </td>
                        <td class="text-sm text-gray-500">{{ $req->created_at->format('Y/m/d') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
        @else
        <div class="card-body text-center text-gray-400 py-12">
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
