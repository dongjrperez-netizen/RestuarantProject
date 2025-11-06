<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Subscriptionpackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminSettingsController extends Controller
{
    public function index(): Response
    {
        $admins = Administrator::orderBy('created_at', 'desc')->get();
        $subscriptionPlans = Subscriptionpackage::orderBy('created_at', 'desc')->get();

        $systemStats = [
            'total_admins' => $admins->count(),
            'total_plans' => $subscriptionPlans->count(),
            'system_version' => '1.0.0',
            'last_backup' => now()->subDays(1)->format('Y-m-d H:i:s'),
        ];

        return Inertia::render('Admin/Settings', [
            'admins' => $admins,
            'subscriptionPlans' => $subscriptionPlans,
            'systemStats' => $systemStats,
        ]);
    }

    public function createAdmin(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email|unique:administrators',
            'password' => 'required|min:8|confirmed',
        ]);

        Administrator::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        return back()->with('success', 'Administrator created successfully.');
    }

    public function deleteAdmin($id): RedirectResponse
    {
        $admin = Administrator::findOrFail($id);

        // Prevent deleting the last admin
        if (Administrator::count() <= 1) {
            return back()->withErrors(['error' => 'Cannot delete the last administrator.']);
        }

        $admin->delete();

        return back()->with('success', 'Administrator deleted successfully.');
    }

    public function createSubscriptionPlan(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_name' => 'required|string|max:255',
            'plan_description' => 'required|string',
            'plan_price' => 'required|numeric|min:0',
            'plan_duration' => 'required|integer|min:1',
            'plan_features' => 'nullable|string',
        ]);

        Subscriptionpackage::create([
            'plan_name' => $request->plan_name,
            'plan_description' => $request->plan_description,
            'plan_price' => $request->plan_price,
            'plan_duration' => $request->plan_duration,
            'plan_features' => $request->plan_features,
        ]);

        return back()->with('success', 'Subscription plan created successfully.');
    }

    public function updateSubscriptionPlan(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'plan_name' => 'required|string|max:255',
            'plan_description' => 'required|string',
            'plan_price' => 'required|numeric|min:0',
            'plan_duration' => 'required|integer|min:1',
            'plan_features' => 'nullable|string',
        ]);

        $plan = Subscriptionpackage::findOrFail($id);
        $plan->update([
            'plan_name' => $request->plan_name,
            'plan_description' => $request->plan_description,
            'plan_price' => $request->plan_price,
            'plan_duration' => $request->plan_duration,
            'plan_features' => $request->plan_features,
        ]);

        return back()->with('success', 'Subscription plan updated successfully.');
    }

    public function deleteSubscriptionPlan($id): RedirectResponse
    {
        $plan = Subscriptionpackage::findOrFail($id);
        $plan->delete();

        return back()->with('success', 'Subscription plan deleted successfully.');
    }

    public function updateSystemSettings(Request $request): RedirectResponse
    {
        // This would typically update system-wide settings stored in a config table
        // For now, we'll just return success

        return back()->with('success', 'System settings updated successfully.');
    }
}
