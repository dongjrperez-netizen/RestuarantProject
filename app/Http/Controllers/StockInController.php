<?php

namespace App\Http\Controllers;

use App\Models\Ingredients;
use App\Models\Restaurant_Data;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockInController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $restaurant = Restaurant_Data::where('user_id', $user->id)->first();

        // Fetch orders with supplier + items + ingredient
        $orders = Restaurant_Order::with(['supplier', 'items.ingredient'])
            ->where('restaurant_id', $restaurant->id)
            ->orderByDesc('order_date')
            ->get()
            ->map(function ($order) {
                return [
                    'order_id' => $order->order_id,
                    'supplier' => $order->supplier
                        ? [
                            'supplier_id' => $order->supplier->supplier_id,
                            'supplier_name' => $order->supplier->supplier_name,
                        ]
                        : null,
                    'reference_no' => $order->reference_no,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount ?? 0,
                    'order_date' => $order->order_date,
                    'items' => $order->items->map(function ($item) {
                        return [
                            'order_item_id' => $item->order_item_id,
                            'ingredient_name' => $item->ingredient?->ingredient_name ?? '-',
                            'item_type' => $item->item_type,
                            'unit' => $item->unit,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                        ];
                    })->toArray(),
                ];
            })->toArray(); // <-- Convert collection to array

        return Inertia::render('Inventory/StockList', [
            'stockOrders' => $orders,
            'totalOrders' => count($orders),
            'receivedOrders' => count(array_filter($orders, fn ($o) => $o['status'] === 'Received')),
            'pendingOrders' => count(array_filter($orders, fn ($o) => $o['status'] === 'Pending')),
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $restaurant = Restaurant_Data::where('user_id', $user->id)->first();

        return Inertia::render('Inventory/StockInForm', [
            'suppliers' => Supplier::all(),
            'ingredients' => Ingredients::all(),
            'restaurant_id' => $restaurant->id,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'restaurant_id' => 'required|exists:restaurant_data,id',
            'reference_no' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,ingredient_id',
            'items.*.item_type' => 'required|string',
            'items.*.unit' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $order = Restaurant_Order::create([
                'restaurant_id' => $data['restaurant_id'],
                'supplier_id' => $data['supplier_id'],
                'reference_no' => $data['reference_no'] ?? null,
                'status' => 'Received',
                'total_amount' => 0, // initial, will update later
            ]);

            $totalAmount = 0;

            foreach ($data['items'] as $item) {
                $total = $item['quantity'] * $item['unit_price'];
                $totalAmount += $total;

                Restaurant_Order_Items::create([
                    'order_id' => $order->order_id,
                    'ingredient_id' => $item['ingredient_id'],
                    'item_type' => $item['item_type'],
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $total,
                ]);

                Ingredients::find($item['ingredient_id'])->increment('current_stock', $item['quantity']);
                $order->update(['total_amount' => $totalAmount]);
            }

            // Update total_amount after creating all items
            // $order->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('stock-in.create')->with('success', 'Stock-in saved successfully!');
    }

    public function storeIngredient(Request $request)
    {
        try {
            $user = auth()->user();
            $restaurant = Restaurant_Data::where('user_id', $user->id)->first();

            $validated = $request->validate([
                'ingredient_name' => 'required|string|max:150',
                'base_unit' => 'required|string|max:50',
                'reorder_level' => 'nullable|numeric|min:0',
            ]);

            DB::beginTransaction();
            $ingredient = Ingredients::create([
                'restaurant_id' => $restaurant->id,
                'ingredient_name' => $validated['ingredient_name'],
                'base_unit' => $validated['base_unit'],
                'reorder_level' => $validated['reorder_level'] ?? 0,
                'current_stock' => 0,
            ]);
            DB::commit();

            return response()->json($ingredient);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
