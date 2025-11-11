<?php

namespace App\Http\Controllers;

use App\Models\Ingredients;
use App\Models\Supplier;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::with(['ingredients', 'purchaseOrders'])
            ->where('restaurant_id', auth()->user()->restaurantData->id)
            ->orderBy('supplier_name')
            ->get();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'restaurant_id' => auth()->user()->restaurantData->id,
        ]);
    }

    public function show($id)
    {
        $supplier = Supplier::with(['ingredients', 'purchaseOrders', 'bills.payments'])
            ->findOrFail($id);

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier,
        ]);
    }

    public function create()
    {
        $limitService = new SubscriptionLimitService();
        $limitCheck = $limitService->canAddSupplier(auth()->user());

        $ingredients = Ingredients::where('restaurant_id', auth()->user()->restaurantData->id)
            ->orderBy('ingredient_name')
            ->get();

        return Inertia::render('Suppliers/Create', [
            'ingredients' => $ingredients,
            'subscriptionLimits' => $limitCheck,
        ]);
    }

    public function store(Request $request)
    {
        // Check subscription limits
        $limitService = new SubscriptionLimitService();
        $limitCheck = $limitService->canAddSupplier(auth()->user());

        if (! $limitCheck['allowed']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['subscription' => $limitCheck['message']]);
        }

        $validated = $request->validate([
            'supplier_name' => 'required|string|max:150',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'address' => 'nullable|string|max:255',
            'business_registration' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'payment_terms' => 'required|in:COD,NET_7,NET_15,NET_30,NET_60,NET_90',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'ingredients' => 'array',
            'ingredients.*.ingredient_id' => 'exists:ingredients,ingredient_id',
            'ingredients.*.package_unit' => 'required|string|max:50',
            'ingredients.*.package_quantity' => 'required|numeric|min:0.01',
            'ingredients.*.package_price' => 'required|numeric|min:0.01',
            'ingredients.*.lead_time_days' => 'nullable|numeric|min:0',
            'ingredients.*.minimum_order_quantity' => 'nullable|numeric|min:1',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurantData->id;

        // If no password provided, generate a temporary one
        if (empty($validated['password'])) {
            $validated['password'] = 'temp_'.uniqid();
        }

        $supplier = Supplier::create($validated);

        if (isset($validated['ingredients'])) {
            foreach ($validated['ingredients'] as $ingredient) {
                $supplier->ingredients()->attach($ingredient['ingredient_id'], [
                    'package_unit' => $ingredient['package_unit'],
                    'package_quantity' => $ingredient['package_quantity'],
                    'package_price' => $ingredient['package_price'],
                    'lead_time_days' => $ingredient['lead_time_days'] ?? 0,
                    'minimum_order_quantity' => $ingredient['minimum_order_quantity'] ?? 1,
                    'is_active' => true,
                ]);
            }
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function edit($id)
    {
        $supplier = Supplier::with(['ingredients'])->findOrFail($id);
        $allIngredients = Ingredients::where('restaurant_id', auth()->user()->restaurantData->id)
            ->orderBy('ingredient_name')
            ->get();

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
            'ingredients' => $allIngredients,
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([
            'supplier_name' => 'required|string|max:150',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'business_registration' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:255',
            'payment_terms' => 'required|in:COD,NET_7,NET_15,NET_30,NET_60,NET_90',
            'credit_limit' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'ingredients' => 'array',
            'ingredients.*.ingredient_id' => 'exists:ingredients,ingredient_id',
            'ingredients.*.package_unit' => 'required|string|max:50',
            'ingredients.*.package_quantity' => 'required|numeric|min:0.01',
            'ingredients.*.package_price' => 'required|numeric|min:0.01',
            'ingredients.*.lead_time_days' => 'nullable|numeric|min:0',
            'ingredients.*.minimum_order_quantity' => 'nullable|numeric|min:1',
        ]);

        $supplier->update($validated);

        if (isset($validated['ingredients'])) {
            $supplier->ingredients()->detach();
            foreach ($validated['ingredients'] as $ingredient) {
                $supplier->ingredients()->attach($ingredient['ingredient_id'], [
                    'package_unit' => $ingredient['package_unit'],
                    'package_quantity' => $ingredient['package_quantity'],
                    'package_price' => $ingredient['package_price'],
                    'lead_time_days' => $ingredient['lead_time_days'] ?? 0,
                    'minimum_order_quantity' => $ingredient['minimum_order_quantity'] ?? 1,
                    'is_active' => true,
                ]);
            }
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_active' => false]);

        return redirect()->route('suppliers.index')->with('success', 'Supplier deactivated successfully.');
    }

    public function toggleStatus($id)
    {
        $supplier = Supplier::where('restaurant_id', auth()->user()->restaurantData->id)
            ->findOrFail($id);

        $supplier->update(['is_active' => ! $supplier->is_active]);

        $status = $supplier->is_active ? 'activated' : 'deactivated';

        return response()->json(['message' => "Supplier {$status} successfully."]);
    }

    public function sendInvitation($id)
    {
        $user = auth()->user();
        $supplier = Supplier::where('restaurant_id', $user->restaurantData->id)
            ->findOrFail($id);

        if (! $supplier->email) {
            return back()->withErrors(['error' => 'Supplier must have an email address.']);
        }

        try {
            // Generate invitation URL using route helper
            $invitationUrl = route('supplier.register', [
                'restaurant_id' => $user->restaurantData->id,
                'supplier_id' => $supplier->supplier_id,
            ]);

            // Send the invitation email
            \Mail::to($supplier->email)->send(new \App\Mail\SupplierInvitation(
                $supplier,
                $user,
                $invitationUrl
            ));

            // Log the invitation
            \Log::info('Supplier invitation sent', [
                'supplier_id' => $supplier->supplier_id,
                'supplier_email' => $supplier->email,
                'restaurant_id' => $user->restaurantData->id,
                'sent_at' => now(),
            ]);

            return back()->with('success', 'Invitation sent successfully to '.$supplier->email);

        } catch (\Exception $e) {
            \Log::error('Failed to send supplier invitation', [
                'supplier_id' => $supplier->supplier_id,
                'supplier_email' => $supplier->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to send invitation. Please try again later.']);
        }
    }
}