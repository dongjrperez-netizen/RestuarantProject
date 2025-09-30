<?php

namespace App\Http\Controllers;

use App\Models\DamageSpoilageLog;
use App\Models\Ingredients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DamageSpoilageController extends Controller
{
    public function index(Request $request)
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id;

        $query = DamageSpoilageLog::with(['ingredient', 'user'])
            ->forRestaurant($restaurantId)
            ->orderBy('incident_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->ingredient_id);
        }

        if ($request->filled('date_from')) {
            $query->where('incident_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('incident_date', '<=', $request->date_to);
        }

        $logs = $query->paginate(15)->withQueryString();

        // Get ingredients for filter dropdown
        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name']);

        return Inertia::render('Kitchen/DamageSpoilage/Index', [
            'logs' => $logs,
            'ingredients' => $ingredients,
            'types' => DamageSpoilageLog::getTypes(),
            'filters' => $request->only(['type', 'ingredient_id', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id;

        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'base_unit']);

        return Inertia::render('Kitchen/DamageSpoilage/Create', [
            'ingredients' => $ingredients,
            'types' => DamageSpoilageLog::getTypes(),
        ]);
    }

    public function store(Request $request)
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id;

        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
                function ($attribute, $value, $fail) use ($restaurantId) {
                    $ingredient = Ingredients::where('ingredient_id', $value)
                        ->where('restaurant_id', $restaurantId)
                        ->first();
                    if (!$ingredient) {
                        $fail('The selected ingredient does not belong to your restaurant.');
                    }
                },
            ],
            'type' => ['required', Rule::in(['damage', 'spoilage'])],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit' => ['required', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        DamageSpoilageLog::create([
            'restaurant_id' => $restaurantId,
            'user_id' => $employee->employee_id,
            ...$validated,
        ]);

        return redirect()->back()
            ->with('success', 'Damage/spoilage log recorded successfully.');
    }

    public function show($id)
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id;

        $log = DamageSpoilageLog::with(['ingredient', 'user', 'restaurant'])
            ->forRestaurant($restaurantId)
            ->findOrFail($id);

        return Inertia::render('Kitchen/DamageSpoilage/Show', [
            'log' => $log,
        ]);
    }

    public function edit($id)
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id;

        $log = DamageSpoilageLog::forRestaurant($restaurantId)->findOrFail($id);

        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get(['ingredient_id', 'ingredient_name', 'base_unit']);

        return Inertia::render('Kitchen/DamageSpoilage/Edit', [
            'log' => $log,
            'ingredients' => $ingredients,
            'types' => DamageSpoilageLog::getTypes(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id;

        $log = DamageSpoilageLog::forRestaurant($restaurantId)->findOrFail($id);

        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
                function ($attribute, $value, $fail) use ($restaurantId) {
                    $ingredient = Ingredients::where('ingredient_id', $value)
                        ->where('restaurant_id', $restaurantId)
                        ->first();
                    if (!$ingredient) {
                        $fail('The selected ingredient does not belong to your restaurant.');
                    }
                },
            ],
            'type' => ['required', Rule::in(['damage', 'spoilage'])],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit' => ['required', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $log->update($validated);

        return redirect()->route('damage-spoilage.index')
            ->with('success', 'Damage/spoilage log updated successfully.');
    }

    public function destroy($id)
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        $restaurantId = $employee->user_id;

        $log = DamageSpoilageLog::forRestaurant($restaurantId)->findOrFail($id);
        $log->delete();

        return redirect()->route('damage-spoilage.index')
            ->with('success', 'Damage/spoilage log deleted successfully.');
    }

    public function summary(Request $request)
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            return response()->json(['error' => 'Access denied. Kitchen staff only.'], 403);
        }

        $restaurantId = $employee->user_id;

        $period = $request->get('period', 30); // Default to last 30 days

        $summary = DamageSpoilageLog::forRestaurant($restaurantId)
            ->recent($period)
            ->selectRaw('
                type,
                COUNT(*) as count,
                SUM(quantity) as total_quantity,
                SUM(estimated_cost) as total_cost
            ')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $topIngredients = DamageSpoilageLog::with('ingredient')
            ->forRestaurant($restaurantId)
            ->recent($period)
            ->selectRaw('
                ingredient_id,
                COUNT(*) as incidents,
                SUM(quantity) as total_quantity,
                SUM(estimated_cost) as total_cost
            ')
            ->groupBy('ingredient_id')
            ->orderBy('incidents', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => $summary,
            'top_ingredients' => $topIngredients,
            'period_days' => $period,
        ]);
    }
}