<?php

namespace App\Http\Controllers;

use App\Models\RegularEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RegularEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurantData->id ?? null;

        if (!$restaurantId) {
            abort(403, 'Restaurant data not found.');
        }

        $query = RegularEmployee::where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $employees = $query->paginate(15)->withQueryString();

        return Inertia::render('RegularEmployees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('RegularEmployees/Create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurantData->id ?? null;

        if (!$restaurantId) {
            abort(403, 'Restaurant data not found.');
        }

        $validated = $request->validate([
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'middle_initial' => 'nullable|string|max:10',
            'age' => 'nullable|integer|min:15|max:100',
            'date_of_birth' => 'required|date|before:today',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $validated['user_id'] = $user->id;
        $validated['restaurant_id'] = $restaurantId;
        $validated['status'] = 'active';

        RegularEmployee::create($validated);

        return redirect()->route('regular-employees.index')
            ->with('success', 'Employee added successfully.');
    }

    public function show(RegularEmployee $regularEmployee)
    {
        $this->authorizeAccess($regularEmployee);

        return Inertia::render('RegularEmployees/Show', [
            'employee' => $regularEmployee,
        ]);
    }

    public function edit(RegularEmployee $regularEmployee)
    {
        $this->authorizeAccess($regularEmployee);

        return Inertia::render('RegularEmployees/Edit', [
            'employee' => $regularEmployee,
        ]);
    }

    public function update(Request $request, RegularEmployee $regularEmployee)
    {
        $this->authorizeAccess($regularEmployee);

        $validated = $request->validate([
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'middle_initial' => 'nullable|string|max:10',
            'age' => 'nullable|integer|min:15|max:100',
            'date_of_birth' => 'required|date|before:today',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
        ]);

        $regularEmployee->update($validated);

        return redirect()->route('regular-employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(RegularEmployee $regularEmployee)
    {
        $this->authorizeAccess($regularEmployee);

        $regularEmployee->delete();

        return redirect()->route('regular-employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    public function activate(RegularEmployee $regularEmployee)
    {
        $this->authorizeAccess($regularEmployee);

        $regularEmployee->update(['status' => 'active']);

        return redirect()->back()
            ->with('success', 'Employee activated successfully.');
    }

    public function deactivate(RegularEmployee $regularEmployee)
    {
        $this->authorizeAccess($regularEmployee);

        $regularEmployee->update(['status' => 'inactive']);

        return redirect()->back()
            ->with('success', 'Employee deactivated successfully.');
    }

    public function toggleStatus(RegularEmployee $regularEmployee)
    {
        $this->authorizeAccess($regularEmployee);

        $newStatus = $regularEmployee->status === 'active' ? 'inactive' : 'active';
        $regularEmployee->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "Employee status changed to {$newStatus}.");
    }

    private function authorizeAccess(RegularEmployee $employee)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurantData->id ?? null;

        if ($employee->restaurant_id !== $restaurantId) {
            abort(403, 'Unauthorized access to employee data.');
        }
    }
}