<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MenuCategoryController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $categories = MenuCategory::byRestaurant($restaurantId)
            ->withCount('dishes')
            ->orderBy('sort_order')
            ->orderBy('category_name')
            ->get();

        return Inertia::render('MenuCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Get the next sort order if not provided
        $sortOrder = $request->sort_order;
        if ($sortOrder === null) {
            $maxSortOrder = MenuCategory::byRestaurant($restaurantId)->max('sort_order') ?? 0;
            $sortOrder = $maxSortOrder + 1;
        }

        MenuCategory::create([
            'restaurant_id' => $restaurantId,
            'category_name' => $request->category_name,
            'description' => $request->description,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);

        return back()->with('success', 'Category created successfully!');
    }

    public function update(Request $request, MenuCategory $category)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $category->update([
            'category_name' => $request->category_name,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? $category->sort_order,
            'is_active' => $request->is_active ?? $category->is_active,
        ]);

        return back()->with('success', 'Category updated successfully!');
    }

    public function destroy(MenuCategory $category)
    {
        // Check if category has dishes
        if ($category->dishes()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing dishes. Please reassign dishes first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully!');
    }

    public function updateStatus(Request $request, MenuCategory $category)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $category->update(['is_active' => $request->is_active]);

        $status = $request->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Category {$status} successfully!");
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.category_id' => 'required|exists:menu_categories,category_id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->categories as $categoryData) {
            MenuCategory::where('category_id', $categoryData['category_id'])
                ->update(['sort_order' => $categoryData['sort_order']]);
        }

        return back()->with('success', 'Categories reordered successfully!');
    }
}