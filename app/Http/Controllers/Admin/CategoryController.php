<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * List all categories.
     */
    public function index()
    {
        $categories = Category::with(['parent', 'courses'])->latest()->paginate(20);
        return view('admin.categories', compact('categories'));
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'icon'       => 'nullable|string|max:10',
            'icon_file'  => 'nullable|file|mimes:jpg,jpeg,png,svg|max:2048',
            'parent_id'  => 'nullable|exists:categories,id',
        ]);

        $imageUrl = null;
        if ($request->hasFile('icon_file')) {
            $path = $request->file('icon_file')->store('categories', 'public');
            $imageUrl = asset('storage/' . $path);
        }

        Category::create([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'icon'      => $imageUrl ? null : $request->icon,
            'image_url' => $imageUrl,
            'parent_id' => $request->parent_id,
            'is_active' => true,
        ]);

        return back()->with('success', 'تم إضافة الفئة بنجاح');
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'icon'      => 'nullable|string|max:10',
            'icon_file' => 'nullable|file|mimes:jpg,jpeg,png,svg|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $imageUrl = $category->image_url;

        if ($request->hasFile('icon_file')) {
            // Delete old uploaded file if it was stored on public disk
            if ($category->image_url && str_contains($category->image_url, '/storage/categories/')) {
                $oldPath = 'categories/' . basename($category->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('icon_file')->store('categories', 'public');
            $imageUrl = asset('storage/' . $path);
        }

        $category->update([
            'name'      => $request->name,
            'slug'      => Str::slug($request->name),
            'icon'      => $imageUrl ? null : $request->icon,
            'image_url' => $imageUrl,
            'parent_id' => $request->parent_id,
        ]);

        return back()->with('success', 'تم تحديث الفئة بنجاح');
    }

    /**
     * Delete a category.
     */
    public function destroy(Category $category)
    {
        // Delete uploaded icon file if present
        if ($category->image_url && str_contains($category->image_url, '/storage/categories/')) {
            $oldPath = 'categories/' . basename($category->image_url);
            Storage::disk('public')->delete($oldPath);
        }
        $category->delete();
        return back()->with('success', 'تم حذف الفئة بنجاح');
    }
}
