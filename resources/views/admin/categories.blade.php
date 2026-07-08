@extends('layouts.admin')

@section('title', 'الفئات')

@section('content')
<div class="card card-body">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-folder"></i>
            إدارة الفئات
        </h2>
        <button onclick="openAddModal()" class="btn-primary">
            <i class="fas fa-plus"></i>
            <span>إضافة فئة</span>
        </button>
    </div>

    @if(session('success'))
    <div class="alert-success mb-5">{{ session('success') }}</div>
    @endif

    <div class="table-container">
        <table class="table-dash">
            <thead>
                <tr>
                    <th>الأيقونة</th>
                    <th>الاسم</th>
                    <th>الفئة الأب</th>
                    <th>الدورات</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                <tr>
                    <td>
                        <div class="avatar avatar-lg bg-brand-50 text-2xl overflow-hidden">
                            @if($category->image_url)
                                <img src="{{ $category->image_url }}" alt="" class="h-full w-full object-cover">
                            @elseif($category->icon)
                                <span>{{ $category->icon }}</span>
                            @else
                                <i class="fas fa-folder text-brand"></i>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                    </td>
                    <td>
                        {{ $category->parent ? $category->parent->name : '-' }}
                    </td>
                    <td>
                        <span class="badge-info">
                            {{ $category->courses->count() }} دورة
                        </span>
                    </td>
                    <td>
                        @if($category->is_active)
                        <span class="badge-success">
                            <i class="fas fa-check-circle"></i> نشط
                        </span>
                        @else
                        <span class="badge-danger">
                            <i class="fas fa-times-circle"></i> معطل
                        </span>
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <button
                                onclick="openEditModal({{ $category->id }}, '{{ addslashes($category->name) }}', '{{ addslashes($category->icon ?? '') }}', '{{ addslashes($category->image_url ?? '') }}', '{{ $category->parent_id }}')"
                                class="text-blue-600 hover:text-blue-900">
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
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="empty-state-title">لا يوجد فئات</div>
                            <div class="empty-state-text">لم يتم إضافة أي فئات بعد</div>
                        </div>
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
<div id="addModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-bold text-gray-800">إضافة فئة جديدة</h3>
            <button type="button" onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ url('/admin/categories') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="form-label">اسم الفئة <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required
                        class="form-input"
                        placeholder="مثال: برمجة، تصميم، لغات...">
                </div>
                <div class="mb-4">
                    <label class="form-label">أيقونة الفئة</label>
                    <div class="flex items-start gap-4">
                        <!-- File upload -->
                        <div class="flex-1">
                            <label class="upload-zone-sm h-28 cursor-pointer">
                                <div id="addUploadHint" class="flex flex-col items-center justify-center">
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
                                class="form-input w-16 text-2xl text-center"
                                placeholder="🎓"
                                oninput="document.getElementById('addIconPreview').textContent = this.value">
                            <span id="addIconPreview" class="text-3xl"></span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">إذا رفعت صورة، تأخذ الأولوية على الإيموجي</p>
                </div>
                <div class="mb-6">
                    <label class="form-label">الفئة الأب</label>
                    <select name="parent_id" class="form-select">
                        <option value="">بدون فئة أب</option>
                        @foreach(\App\Models\Category::whereNull('parent_id')->get() as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        إضافة
                    </button>
                    <button type="button" onclick="closeAddModal()" class="btn-secondary flex-1">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="text-lg font-bold text-gray-800">تعديل الفئة</h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="editForm" method="POST" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="form-label">اسم الفئة <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="editName" required
                        class="form-input">
                </div>
                <div class="mb-4">
                    <label class="form-label">أيقونة الفئة</label>
                    <div class="flex items-start gap-4">
                        <!-- File upload -->
                        <div class="flex-1">
                            <label class="upload-zone-sm h-28 cursor-pointer">
                                <div id="editUploadHint" class="flex flex-col items-center justify-center">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-1"></i>
                                    <p class="text-xs text-gray-500">JPG / PNG / SVG</p>
                                    <p id="editCurrentIconLabel" class="text-xs text-brand"></p>
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
                                class="form-input w-16 text-2xl text-center"
                                oninput="document.getElementById('editIconPreview').textContent = this.value || ''">
                            <span id="editIconPreview" class="text-3xl"></span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">إذا رفعت صورة جديدة، ستحل محل الأيقونة الحالية</p>
                </div>
                <div class="mb-6">
                    <label class="form-label">الفئة الأب</label>
                    <select name="parent_id" id="editParentId" class="form-select">
                        <option value="">بدون فئة أب</option>
                        @foreach(\App\Models\Category::whereNull('parent_id')->get() as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        حفظ التعديلات
                    </button>
                    <button type="button" onclick="closeEditModal()" class="btn-secondary flex-1">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
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
