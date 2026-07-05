@extends('layouts.admin')

@section('title', 'الفئات')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold flex items-center">
            <i class="fas fa-folder text-green-600 mr-3"></i>
            إدارة الفئات
        </h2>
        <button onclick="openAddModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center space-x-2 space-x-reverse btn-primary">
            <i class="fas fa-plus"></i>
            <span>إضافة فئة</span>
        </button>
    </div>

    @if(session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-green-600 to-green-700 text-white">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الأيقونة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الاسم</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الفئة الأب</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الدورات</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex-shrink-0 h-12 w-12 bg-green-100 rounded-full flex items-center justify-center text-2xl overflow-hidden">
                            @if($category->image_url)
                                <img src="{{ $category->image_url }}" alt="" class="h-12 w-12 rounded-full object-cover">
                            @elseif($category->icon)
                                <span>{{ $category->icon }}</span>
                            @else
                                <i class="fas fa-folder text-green-600"></i>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $category->parent ? $category->parent->name : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                            {{ $category->courses->count() }} دورة
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($category->is_active)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> نشط
                        </span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i> معطل
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2 space-x-reverse">
                        <button
                            onclick="openEditModal({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->icon ?? '') }}', '{{ addslashes($category->image_url ?? '') }}', '{{ $category->parent_id }}')"
                            class="text-blue-600 hover:text-blue-900 mr-2">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" action="{{ url('/admin/categories/' . $category->id) }}" style="display:inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذه الفئة؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fas fa-folder text-4xl mb-2"></i>
                        <p>لا يوجد فئات</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $categories->links() }}
    </div>
</div>

<!-- Add Category Modal -->
<div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
        <h3 class="text-2xl font-bold mb-6">إضافة فئة جديدة</h3>
        <form method="POST" action="{{ url('/admin/categories') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">اسم الفئة <span class="text-red-500">*</span></label>
                <input type="text" name="name" required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-green-400"
                    placeholder="مثال: برمجة، تصميم، لغات...">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">أيقونة الفئة</label>
                <div class="flex items-start gap-4">
                    <!-- File upload -->
                    <div class="flex-1">
                        <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-green-500 hover:bg-green-50 transition">
                            <div id="addUploadHint" class="flex flex-col items-center justify-center pt-2 pb-2">
                                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-1"></i>
                                <p class="text-xs text-gray-500">JPG / PNG / SVG</p>
                                <p class="text-xs text-gray-400">حد أقصى 2MB</p>
                            </div>
                            <img id="addImgPreview" src="" alt="" class="hidden h-24 w-24 object-contain rounded">
                            <input type="file" name="icon_file" id="addIconFile" accept="image/jpeg,image/png,image/svg+xml" class="hidden"
                                onchange="previewImage(this, 'addImgPreview', 'addUploadHint')">
                        </label>
                    </div>
                    <!-- OR emoji -->
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-xs text-gray-500 font-bold">أو إيموجي</span>
                        <input type="text" name="icon" id="addIconInput" maxlength="10"
                            class="border rounded w-16 py-2 text-2xl text-center focus:outline-none focus:ring-2 focus:ring-green-400"
                            placeholder="🎓"
                            oninput="document.getElementById('addIconPreview').textContent = this.value">
                        <span id="addIconPreview" class="text-3xl"></span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">إذا رفعت صورة، تأخذ الأولوية على الإيموجي</p>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">الفئة الأب</label>
                <select name="parent_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-green-400">
                    <option value="">بدون فئة أب</option>
                    @foreach(\App\Models\Category::whereNull('parent_id')->get() as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex space-x-4 space-x-reverse">
                <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    إضافة
                </button>
                <button type="button" onclick="closeAddModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
        <h3 class="text-2xl font-bold mb-6">تعديل الفئة</h3>
        <form id="editForm" method="POST" action="" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">اسم الفئة <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="editName" required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">أيقونة الفئة</label>
                <div class="flex items-start gap-4">
                    <!-- File upload -->
                    <div class="flex-1">
                        <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition">
                            <div id="editUploadHint" class="flex flex-col items-center justify-center pt-2 pb-2">
                                <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-1"></i>
                                <p class="text-xs text-gray-500">JPG / PNG / SVG</p>
                                <p id="editCurrentIconLabel" class="text-xs text-blue-500"></p>
                            </div>
                            <img id="editImgPreview" src="" alt="" class="hidden h-24 w-24 object-contain rounded">
                            <input type="file" name="icon_file" id="editIconFile" accept="image/jpeg,image/png,image/svg+xml" class="hidden"
                                onchange="previewImage(this, 'editImgPreview', 'editUploadHint')">
                        </label>
                    </div>
                    <!-- OR emoji -->
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-xs text-gray-500 font-bold">أو إيموجي</span>
                        <input type="text" name="icon" id="editIconInput" maxlength="10"
                            class="border rounded w-16 py-2 text-2xl text-center focus:outline-none focus:ring-2 focus:ring-blue-400"
                            oninput="document.getElementById('editIconPreview').textContent = this.value || ''">
                        <span id="editIconPreview" class="text-3xl"></span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-1">إذا رفعت صورة جديدة، ستحل محل الأيقونة الحالية</p>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">الفئة الأب</label>
                <select name="parent_id" id="editParentId" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">بدون فئة أب</option>
                    @foreach(\App\Models\Category::whereNull('parent_id')->get() as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex space-x-4 space-x-reverse">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    حفظ التعديلات
                </button>
                <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input, previewId, hintId) {
    const preview = document.getElementById(previewId);
    const hint    = document.getElementById(hintId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            hint.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}
function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(id, name, icon, imageUrl, parentId) {
    document.getElementById('editForm').action = '/admin/categories/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editIconInput').value = icon;
    document.getElementById('editIconPreview').textContent = icon || '';

    // Show current image if exists
    const preview = document.getElementById('editImgPreview');
    const hint    = document.getElementById('editUploadHint');
    const label   = document.getElementById('editCurrentIconLabel');
    if (imageUrl) {
        preview.src = imageUrl;
        preview.classList.remove('hidden');
        hint.classList.add('hidden');
        label.textContent = 'الصورة الحالية';
    } else {
        preview.src = '';
        preview.classList.add('hidden');
        hint.classList.remove('hidden');
        label.textContent = '';
    }
    // Reset file input
    document.getElementById('editIconFile').value = '';

    const sel = document.getElementById('editParentId');
    for (let opt of sel.options) {
        opt.selected = (opt.value == parentId);
    }
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>
@endsection

