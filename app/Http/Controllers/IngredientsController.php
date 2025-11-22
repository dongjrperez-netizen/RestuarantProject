<?php

namespace App\Http\Controllers;

use App\Models\Ingredients;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IngredientsController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user->restaurantData) {
            return redirect()->route('dashboard')
                ->with('error', 'Please complete your restaurant setup first.');
        }

        $restaurantId = $user->restaurantData->id;

        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get()
            ->map(function ($ingredient) {
                return [
                    'ingredient_id' => $ingredient->ingredient_id,
                    'ingredient_name' => $ingredient->ingredient_name,
                    'base_unit' => $ingredient->base_unit,
                    'cost_per_unit' => $ingredient->cost_per_unit,
                    'current_stock' => $ingredient->current_stock,
                    'reorder_level' => $ingredient->reorder_level,
                    'packages' => $ingredient->packages,
                    'is_low_stock' => $ingredient->current_stock <= $ingredient->reorder_level,
                ];
            });

        return Inertia::render('Ingredients/Index', [
            'ingredients' => $ingredients,
        ]);
    }

    public function create()
    {
        return Inertia::render('Ingredients/Create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->restaurantData) {
            return redirect()->back()
                ->with('error', 'No restaurant data found.');
        }

        $validated = $request->validate([
            'ingredient_name' => 'required|string|max:255',
            'base_unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'packages' => 'nullable|integer|min:0',
        ]);

        $ingredient = Ingredients::create([
            'restaurant_id' => $user->restaurantData->id,
            'ingredient_name' => $validated['ingredient_name'],
            'base_unit' => $validated['base_unit'],
            'cost_per_unit' => $validated['cost_per_unit'],
            'current_stock' => $validated['current_stock'] ?? 0,
            'reorder_level' => $validated['reorder_level'] ?? 0,
            'packages' => $validated['packages'] ?? 0,
        ]);

        return redirect()->route('ingredients.index')
            ->with('success', 'Ingredient added successfully!');
    }

    public function edit($id)
    {
        $user = auth()->user();

        if (!$user->restaurantData) {
            return redirect()->back()
                ->with('error', 'No restaurant data found.');
        }

        $ingredient = Ingredients::where('ingredient_id', $id)
            ->where('restaurant_id', $user->restaurantData->id)
            ->firstOrFail();

        return Inertia::render('Ingredients/Edit', [
            'ingredient' => [
                'ingredient_id' => $ingredient->ingredient_id,
                'ingredient_name' => $ingredient->ingredient_name,
                'base_unit' => $ingredient->base_unit,
                'cost_per_unit' => $ingredient->cost_per_unit,
                'current_stock' => $ingredient->current_stock,
                'reorder_level' => $ingredient->reorder_level,
                'packages' => $ingredient->packages,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user->restaurantData) {
            return redirect()->back()
                ->with('error', 'No restaurant data found.');
        }

        $ingredient = Ingredients::where('ingredient_id', $id)
            ->where('restaurant_id', $user->restaurantData->id)
            ->firstOrFail();

        $validated = $request->validate([
            'ingredient_name' => 'required|string|max:255',
            'base_unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'packages' => 'nullable|integer|min:0',
        ]);

        $ingredient->update([
            'ingredient_name' => $validated['ingredient_name'],
            'base_unit' => $validated['base_unit'],
            'cost_per_unit' => $validated['cost_per_unit'],
            'current_stock' => $validated['current_stock'] ?? 0,
            'reorder_level' => $validated['reorder_level'] ?? 0,
            'packages' => $validated['packages'] ?? 0,
        ]);

        return redirect()->route('ingredients.index')
            ->with('success', 'Ingredient updated successfully!');
    }

    public function destroy($id)
    {
        $user = auth()->user();

        if (!$user->restaurantData) {
            return response()->json(['error' => 'No restaurant data found.'], 403);
        }

        $ingredient = Ingredients::where('ingredient_id', $id)
            ->where('restaurant_id', $user->restaurantData->id)
            ->firstOrFail();

        // Check if ingredient is used in any dishes or purchase orders
        if ($ingredient->dishIngredients()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete ingredient that is used in dishes.');
        }

        if ($ingredient->purchaseOrderItems()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete ingredient that has purchase order history.');
        }

        $ingredient->delete();

        return redirect()->route('ingredients.index')
            ->with('success', 'Ingredient deleted successfully!');
    }
}
