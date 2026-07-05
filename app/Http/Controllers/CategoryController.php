<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // Public: Get all categories
    public function index()
    {
        $categories = CacheService::remember(
            CacheService::categoriesKey(),
            CacheService::TTL_VERY_LONG,
            fn() => Category::where('is_active', true)
                ->with('children')
                ->whereNull('parent_id')
                ->orderBy('order')
                ->get()
        );

        return response()->json(['success' => true, 'data' => $categories]);
    }

    // Public: Get category details
    public function show($id)
    {
        $category = Category::with(['parent', 'children', 'courses'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    // Admin: Store category
    public function store(\App\Http\Requests\StoreCategoryRequest $request)
    {
        $category = Category::create([
            'name' => $request->name,
            'slug' => \Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'parent_id' => $request->parent_id,
            'order' => $request->order ?? 0,
            'image_url' => $request->image_url,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    // Admin: Update category
    public function update(\App\Http\Requests\UpdateCategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    // Admin: Delete category
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}
