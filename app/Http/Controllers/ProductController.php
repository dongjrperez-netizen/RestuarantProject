<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $restaurantId = DB::table('restaurant_data')
            ->where('user_id', auth()->id())
            ->value('id');
        $products = Product::where('restaurant_id', $restaurantId)->paginate(10);

        $products = Product::where('restaurant_id', $restaurantId)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'status' => 'Available',
                ];
            });

        return Inertia::render('products/product', [
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'product_name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'price' => 'required|integer',
            ]);

            $restaurantId = DB::table('restaurant_data')
                ->where('user_id', auth()->id())
                ->value('id');

            if (! $restaurantId) {
                return redirect()->back()->withErrors([
                    'error' => 'No restaurant linked to your account.',
                ]);
            }

            Product::create([
                'product_name' => $validated['product_name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'restaurant_id' => $restaurantId,
            ]);

            return redirect()->route('product.index')->with('success', 'Product created!');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }
}
