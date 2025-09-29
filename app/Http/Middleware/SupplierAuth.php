<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SupplierAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('supplier')->check()) {
            return redirect()->route('login');
        }

        $supplier = Auth::guard('supplier')->user();

        if (!$supplier->is_active) {
            Auth::guard('supplier')->logout();
            return redirect()->route('login')->with('error', 'Your supplier account is not active. Please contact the restaurant.');
        }

        return $next($request);
    }
}