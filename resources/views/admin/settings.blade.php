@extends('layouts.admin')

@section('title', 'إعدادات المنصة')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 max-w-2xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-cog text-green-600 mr-3"></i>
            إعدادات المنصة
        </h2>
        <p class="text-sm text-gray-500 mt-1">تُستخدم هذه الإعدادات في الشهادات والواجهة العامة</p>
    </div>

    <form method="POST" action="{{ url('/admin/settings') }}" enctype="multipart/form-data">
        @csrf

        {{-- Platform Name --}}
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                اسم المنصة <span class="text-red-500">*</span>
            </label>
            <input type="text" name="platform_name" value="{{ old('platform_name', $settings['platform_name']) }}" required
                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-green-400"
                placeholder="EdLibya">
            @error('platform_name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        {{-- Platform Logo --}}
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">شعار المنصة (Logo)</label>

            @if(!empty($settings['platform_logo_url']))
            <div class="mb-3 flex items-center gap-4">
                <img src="{{ $settings['platform_logo_url'] }}" alt="الشعار الحالي" class="h-16 object-contain border rounded p-1">
                <span class="text-sm text-gray-500">الشعار الحالي — رفع صورة جديدة يستبدله</span>
            </div>
            @endif

            <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-green-500 hover:bg-green-50 transition">
                <div id="logoHint" class="flex flex-col items-center justify-center">
                    <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-1"></i>
                    <p class="text-xs text-gray-500">JPG / PNG / SVG — حد أقصى 2MB</p>
                </div>
                <img id="logoPreview" src="" alt="" class="hidden h-20 object-contain rounded">
                <input type="file" name="logo_file" id="logoFile" accept="image/jpeg,image/png,image/svg+xml" class="hidden"
                    onchange="
                        const f = this.files[0];
                        if (f) {
                            const r = new FileReader();
                            r.onload = e => {
                                document.getElementById('logoPreview').src = e.target.result;
                                document.getElementById('logoPreview').classList.remove('hidden');
                                document.getElementById('logoHint').classList.add('hidden');
                            };
                            r.readAsDataURL(f);
                        }
                    ">
            </label>
            @error('logo_file')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:ring-2 focus:ring-green-400">
            <i class="fas fa-save ml-2"></i>حفظ الإعدادات
        </button>
    </form>
</div>
@endsection
