<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Role;
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
        $roles = Role::all();

        return Inertia::render('UserManagement/Employees', [
            'employees' => $employees,
            'roles' => $roles,
            'filters' => $request->only(['search', 'status', 'role_id']),
        ]);
    }

    public function create()
    {
        $roles = Role::all();

        return Inertia::render('UserManagement/CreateEmployee', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:employees',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'role_id' => 'required|exists:roles,id',
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
        $roles = Role::all();

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
