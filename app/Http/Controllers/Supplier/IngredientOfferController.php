<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Ingredients;
use App\Models\Restaurant_Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class IngredientOfferController extends Controller
{
    public function index()
    {
        $supplier = Auth::guard('supplier')->user();

        // Get all ingredients offered by this supplier with restaurant details
        $offeredIngredients = $supplier->ingredients()
            ->with(['restaurant' => function ($query) {
                $query->select('id', 'restaurant_name', 'address');
            }])
            ->withPivot(['package_unit', 'package_quantity', 'package_contents_quantity', 'package_contents_unit', 'package_price', 'minimum_order_quantity', 'is_active'])
            ->orderBy('ingredient_name')
            ->get();

        return Inertia::render('Supplier/Ingredients/Index', [
            'offeredIngredients' => $offeredIngredients,
            'supplier' => $supplier,
        ]);
    }

    public function create()
    {
        $supplier = Auth::guard('supplier')->user();

        // Get only the restaurant that invited this supplier
        $restaurant = Restaurant_Data::select('id', 'restaurant_name', 'address')
            ->where('id', $supplier->restaurant_id)
            ->first();

        return Inertia::render('Supplier/Ingredients/Create', [
            'restaurant' => $restaurant,
        ]);
    }

    public function store(Request $request)
    {
        $supplier = Auth::guard('supplier')->user();

        $request->validate([
            'ingredient_name' => 'required|string|max:150',
            'package_unit' => 'required|string|max:50',
            'package_quantity' => 'required|numeric|min:0.01',
            'package_contents_quantity' => 'required|numeric|min:0.01',
            'package_contents_unit' => 'required|string|max:50',
            'package_price' => 'required|numeric|min:0.01',
            'minimum_order_quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            // Use the supplier's associated restaurant_id automatically
            $restaurantId = $supplier->restaurant_id;

            // First, create or find the ingredient in the restaurant's ingredients table
            // Use package_contents_unit as the base_unit for the ingredient
            $ingredient = Ingredients::firstOrCreate([
                'restaurant_id' => $restaurantId,
                'ingredient_name' => $request->ingredient_name,
                'base_unit' => $request->package_contents_unit,
            ], [
                'current_stock' => 0,
                'reorder_level' => 0,
            ]);

            // Check if this supplier already offers this ingredient to this restaurant
            $existingOffer = DB::table('ingredient_suppliers')
                ->where('supplier_id', $supplier->supplier_id)
                ->where('ingredient_id', $ingredient->ingredient_id)
                ->exists();

            if ($existingOffer) {
                DB::rollBack();

                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You already offer this ingredient to this restaurant. Please update the existing offer instead.');
            }

            // Create the supplier ingredient offer
            DB::table('ingredient_suppliers')->insert([
                'ingredient_id' => $ingredient->ingredient_id,
                'supplier_id' => $supplier->supplier_id,
                'package_unit' => $request->package_unit,
                'package_quantity' => $request->package_quantity,
                'package_contents_quantity' => $request->package_contents_quantity,
                'package_contents_unit' => $request->package_contents_unit,
                'package_price' => $request->package_price,
                'minimum_order_quantity' => $request->minimum_order_quantity,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('supplier.ingredients.index')
                ->with('success', 'Ingredient offer added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while adding the ingredient offer. Please try again.');
        }
    }

    public function edit($ingredientId)
    {
        $supplier = Auth::guard('supplier')->user();

        $ingredient = $supplier->ingredients()
            ->with(['restaurant' => function ($query) {
                $query->select('id', 'restaurant_name');
            }])
            ->withPivot(['package_unit', 'package_quantity', 'package_contents_quantity', 'package_contents_unit', 'package_price', 'minimum_order_quantity', 'is_active'])
            ->where('ingredients.ingredient_id', $ingredientId)
            ->firstOrFail();

        return Inertia::render('Supplier/Ingredients/Edit', [
            'ingredient' => $ingredient,
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, $ingredientId)
    {
        $supplier = Auth::guard('supplier')->user();

        $request->validate([
            'package_unit' => 'required|string|max:50',
            'package_quantity' => 'required|numeric|min:0.01',
            'package_contents_quantity' => 'required|numeric|min:0.01',
            'package_contents_unit' => 'required|string|max:50',
            'package_price' => 'required|numeric|min:0.01',
            'minimum_order_quantity' => 'required|numeric|min:0.01',
            'is_active' => 'required|boolean',
        ]);

        $supplier->ingredients()->updateExistingPivot($ingredientId, [
            'package_unit' => $request->package_unit,
            'package_quantity' => $request->package_quantity,
            'package_contents_quantity' => $request->package_contents_quantity,
            'package_contents_unit' => $request->package_contents_unit,
            'package_price' => $request->package_price,
            'minimum_order_quantity' => $request->minimum_order_quantity,
            'is_active' => $request->is_active,
            'updated_at' => now(),
        ]);

        return redirect()->route('supplier.ingredients.index')
            ->with('success', 'Ingredient offer updated successfully.');
    }

    public function destroy($ingredientId)
    {
        $supplier = Auth::guard('supplier')->user();

        $supplier->ingredients()->detach($ingredientId);

        return redirect()->route('supplier.ingredients.index')
            ->with('success', 'Ingredient offer removed successfully.');
    }
}
