<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['role'])
            ->forRestaurant(Auth::id())
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->get('role_id'));
        }

        $employees = $query->paginate(15)->withQueryString();
        // Only allow these roles in user management filters
        $roles = Role::whereIn('role_name', ['Manager', 'Cashier', 'Waiter', 'Kitchen'])->get();

        return Inertia::render('UserManagement/Employees', [
            'employees' => $employees,
            'roles' => $roles,
            'filters' => $request->only(['search', 'status', 'role_id']),
        ]);
    }

    public function create()
    {
        $limitService = new SubscriptionLimitService();
        $limitCheck = $limitService->canAddEmployee(Auth::user());
 
        // Only allow these roles when creating an employee from user management
        $roles = Role::whereIn('role_name', ['Manager', 'Cashier', 'Waiter', 'Kitchen'])->get();

        return Inertia::render('UserManagement/CreateEmployee', [
            'roles' => $roles,
            'subscriptionLimits' => $limitCheck,
        ]);
    }

    public function store(Request $request)
    {
        // Check subscription limits
        $limitService = new SubscriptionLimitService();
        $limitCheck = $limitService->canAddEmployee(Auth::user());

        if (! $limitCheck['allowed']) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['subscription' => $limitCheck['message']]);
        }

        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'lastname' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'middlename' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'email' => 'required|string|email|max:255|unique:employees',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'role_id' => 'required|exists:roles,id',
            'manager_access_code' => ['nullable', 'digits:6'],
        ], [
            'firstname.required' => 'First name is required.',
            'firstname.regex' => 'First name can only contain letters, spaces, hyphens, and apostrophes.',
            'lastname.required' => 'Last name is required.',
            'lastname.regex' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.',
            'middlename.regex' => 'Middle name can only contain letters, spaces, hyphens, and apostrophes.',
            'manager_access_code.digits' => 'Manager access code must be exactly 6 digits.',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['user_id'] = Auth::id();
        $validated['status'] = 'active';

        Employee::create($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $this->authorizeEmployeeAccess($employee);

        $employee->load(['role']);

        return Inertia::render('UserManagement/ShowEmployee', [
            'employee' => $employee,
        ]);
    }

    public function edit(Employee $employee)
    {
        $this->authorizeEmployeeAccess($employee);
 
        $employee->load(['role']);
        // Only allow these roles when editing an employee from user management
        $roles = Role::whereIn('role_name', ['Manager', 'Cashier', 'Waiter', 'Kitchen'])->get();

        return Inertia::render('UserManagement/EditEmployee', [
            'employee' => $employee,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($employee);

        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('employees')->ignore($employee->employee_id, 'employee_id'),
            ],
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive',
            'manager_access_code' => ['nullable', 'digits:6'],
        ], [
            'manager_access_code.digits' => 'Manager access code must be exactly 6 digits.',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeEmployeeAccess($employee);

        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }

    public function toggleStatus(Employee $employee)
    {
        $this->authorizeEmployeeAccess($employee);

        $newStatus = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "Employee status changed to {$newStatus}.");
    }

    private function authorizeEmployeeAccess(Employee $employee)
    {
        if ($employee->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to employee data.');
        }
    }
}
