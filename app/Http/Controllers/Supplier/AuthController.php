<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Supplier/Auth/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('supplier')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/supplier/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => __('The provided credentials do not match our records.'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('supplier')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/supplier/login');
    }

    public function showRegisterForm(Request $request)
    {
        $restaurantId = $request->query('restaurant_id');
        $supplierId = $request->query('supplier_id');

        if (! $restaurantId) {
            abort(403, 'Invalid registration link');
        }

        $supplier = null;
        if ($supplierId) {
            $supplier = \App\Models\Supplier::where('supplier_id', $supplierId)
                ->where('restaurant_id', $restaurantId)
                ->first();

            if (! $supplier) {
                abort(404, 'Supplier not found');
            }
        }

        return Inertia::render('Supplier/Auth/Register', [
            'restaurant_id' => $restaurantId,
            'supplier' => $supplier,
        ]);
    }

    public function register(Request $request)
    {
        // Validate email uniqueness, but ignore current supplier if updating
        $emailRule = 'required|string|email|max:255|unique:suppliers,email';
        if ($request->supplier_id) {
            $emailRule .= ','.$request->supplier_id.',supplier_id';
        }

        $request->validate([
            'restaurant_id' => 'required|exists:restaurant_data,id',
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'supplier_name' => 'required|string|max:150',
            'email' => $emailRule,
            'password' => 'required|string|min:8|confirmed',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'business_registration' => 'nullable|string',
            'tax_id' => 'nullable|string',
        ]);

        // If supplier_id is provided, update existing supplier
        if ($request->supplier_id) {
            $supplier = \App\Models\Supplier::where('supplier_id', $request->supplier_id)
                ->where('restaurant_id', $request->restaurant_id)
                ->firstOrFail();

            // Update existing supplier with registration details
            $supplier->update([
                'supplier_name' => $request->supplier_name,
                'email' => $request->email,
                'password' => $request->password,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'business_registration' => $request->business_registration,
                'tax_id' => $request->tax_id,
                'is_active' => true,
                'email_verified_at' => now(), // Mark as verified after registration
            ]);
        } else {
            // Create new supplier if no supplier_id provided
            $supplier = \App\Models\Supplier::create([
                'restaurant_id' => $request->restaurant_id,
                'supplier_name' => $request->supplier_name,
                'email' => $request->email,
                'password' => $request->password,
                'contact_number' => $request->contact_number,
                'address' => $request->address,
                'business_registration' => $request->business_registration,
                'tax_id' => $request->tax_id,
                'payment_terms' => 'NET_30',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        Auth::guard('supplier')->login($supplier);

        return redirect('/supplier/dashboard');
    }
}
