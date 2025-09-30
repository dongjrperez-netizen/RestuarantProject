<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\MenuCategory;
use App\Models\DishIngredient;
use App\Models\Ingredients;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $query = Dish::with(['category'])
            ->byRestaurant($restaurantId);

        // Apply filters
        if ($request->search) {
            $query->where('dish_name', 'like', '%' . $request->search . '%');
        }

        if ($request->category_id) {
            $query->byCategory($request->category_id);
        }

        if ($request->status) {
            $query->byStatus($request->status);
        }

        $dishes = $query->orderBy('dish_name')->get();

        // Get categories for filter dropdown
        $categories = MenuCategory::byRestaurant($restaurantId)
            ->active()
            ->orderBy('sort_order')
            ->get(['category_id', 'category_name']);

        return Inertia::render('Menu/Index', [
            'dishes' => $dishes,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status']),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $categories = MenuCategory::byRestaurant($restaurantId)
            ->active()
            ->orderBy('sort_order')
            ->get(['category_id', 'category_name']);

        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get([
                'ingredient_id',
                'ingredient_name',
                'base_unit',
                'current_stock',
                'cost_per_unit'
            ]);

        $availableUnits = [
            'weight' => ['g', 'kg', 'lb', 'oz'],
            'volume' => ['ml', 'l', 'cup', 'tbsp', 'tsp'],
            'count' => ['pcs', 'item', 'unit'],
        ];

        return Inertia::render('Menu/Create', [
            'categories' => $categories,
            'ingredients' => $ingredients,
            'availableUnits' => $availableUnits,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'dish_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:menu_categories,category_id',
            'image_url' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'nullable|exists:ingredients,ingredient_id',
            'ingredients.*.ingredient_name' => 'required|string|max:255',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.unit' => 'required|string|max:50',
            'ingredients.*.is_optional' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $user) {
            // Create the dish
            $dish = Dish::create([
                'restaurant_id' => $user->restaurant_id ?? ($user->restaurantData->id ?? null),
                'dish_name' => $request->dish_name,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'image_url' => $request->image_url,
                'price' => $request->price,
                'status' => 'active', // Set as active by default for simplified workflow
            ]);

            // Add ingredients
            foreach ($request->ingredients as $ingredientData) {
                $ingredient = null;

                if (!empty($ingredientData['ingredient_id']) && $ingredientData['ingredient_id'] > 0) {
                    // Existing ingredient
                    $ingredient = Ingredients::find($ingredientData['ingredient_id']);
                } else {
                    // Custom ingredient - create new one
                    $ingredient = Ingredients::create([
                        'restaurant_id' => $user->restaurant_id ?? ($user->restaurantData->id ?? null),
                        'ingredient_name' => $ingredientData['ingredient_name'],
                        'base_unit' => $ingredientData['unit'],
                        'current_stock' => 0,
                    ]);
                }

                if ($ingredient) {
                    DishIngredient::create([
                        'dish_id' => $dish->dish_id,
                        'ingredient_id' => $ingredient->ingredient_id,
                        'quantity_needed' => $ingredientData['quantity'],
                        'unit_of_measure' => $ingredientData['unit'],
                        'is_optional' => $ingredientData['is_optional'] ?? false,
                    ]);
                }
            }

        });

        return redirect()->route('menu.index')->with('success', 'Dish created successfully!');
    }

    public function show(Dish $dish)
    {
        $dish->load([
            'category',
            'dishIngredients.ingredient'
        ]);

        return Inertia::render('Menu/Show', [
            'dish' => $dish,
        ]);
    }

    public function edit(Dish $dish)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $dish->load([
            'dishIngredients.ingredient'
        ]);

        $categories = MenuCategory::byRestaurant($restaurantId)
            ->active()
            ->orderBy('sort_order')
            ->get(['category_id', 'category_name']);

        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get([
                'ingredient_id',
                'ingredient_name',
                'base_unit',
                'current_stock',
                'cost_per_unit'
            ]);

        $availableUnits = [
            'weight' => ['g', 'kg', 'lb', 'oz'],
            'volume' => ['ml', 'l', 'cup', 'tbsp', 'tsp'],
            'count' => ['pcs', 'item', 'unit'],
        ];

        return Inertia::render('Menu/Edit', [
            'dish' => $dish,
            'categories' => $categories,
            'ingredients' => $ingredients,
            'availableUnits' => $availableUnits,
        ]);
    }

    public function update(Request $request, Dish $dish)
    {
        $request->validate([
            'dish_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:menu_categories,category_id',
            'image_url' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ingredient_id' => 'nullable|exists:ingredients,ingredient_id',
            'ingredients.*.ingredient_name' => 'required|string|max:255',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.unit' => 'required|string|max:50',
            'ingredients.*.is_optional' => 'boolean',
        ]);

        DB::transaction(function () use ($request, $dish) {
            // Update dish basic info
            $dish->update([
                'dish_name' => $request->dish_name,
                'description' => $request->description,
                'category_id' => $request->category_id,
                'image_url' => $request->image_url,
                'price' => $request->price,
            ]);

            // Update ingredients - delete all and recreate
            $dish->dishIngredients()->delete();
            foreach ($request->ingredients as $ingredientData) {
                $ingredient = null;

                if (!empty($ingredientData['ingredient_id']) && $ingredientData['ingredient_id'] > 0) {
                    // Existing ingredient
                    $ingredient = Ingredients::find($ingredientData['ingredient_id']);
                } else {
                    // Custom ingredient - create new one
                    $ingredient = Ingredients::create([
                        'restaurant_id' => $dish->restaurant_id,
                        'ingredient_name' => $ingredientData['ingredient_name'],
                        'base_unit' => $ingredientData['unit'],
                        'current_stock' => 0,
                    ]);
                }

                if ($ingredient) {
                    DishIngredient::create([
                        'dish_id' => $dish->dish_id,
                        'ingredient_id' => $ingredient->ingredient_id,
                        'quantity_needed' => $ingredientData['quantity'],
                        'unit_of_measure' => $ingredientData['unit'],
                        'is_optional' => $ingredientData['is_optional'] ?? false,
                    ]);
                }
            }
        });

        return redirect()->route('menu.show', $dish)->with('success', 'Dish updated successfully!');
    }

    public function updateStatus(Request $request, Dish $dish)
    {
        $request->validate([
            'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'archived'])],
        ]);

        $dish->update(['status' => $request->status]);

        return back()->with('success', 'Dish status updated successfully!');
    }

    public function destroy(Dish $dish)
    {
        $dish->delete();

        return redirect()->route('menu.index')->with('success', 'Dish deleted successfully!');
    }

    public function analytics()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        // Get analytics data
        $analytics = [
            'total_dishes' => Dish::byRestaurant($restaurantId)->count(),
            'active_dishes' => Dish::byRestaurant($restaurantId)->active()->count(),
            'popular_categories' => MenuCategory::byRestaurant($restaurantId)
                ->withCount('dishes')
                ->orderBy('dishes_count', 'desc')
                ->limit(5)
                ->get(),
            'cost_analysis' => null,
        ];

        return Inertia::render('Menu/Analytics', [
            'analytics' => $analytics,
        ]);
    }
}