<?php

namespace App\Http\Controllers;

use App\Models\DamageSpoilageLog;
use App\Models\Ingredients;
use App\Models\Restaurant_Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        // Resolve the actual restaurant_data.id for this employee's restaurant owner
        $restaurant = Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (! $restaurant) {
            abort(403, 'No restaurant data found for this employee.');
        }
        $restaurantId = $restaurant->id;

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
        $ingredients = Ingredients::whereHas('restaurant', function ($query) use ($employee) {
                $query->where('user_id', $employee->user_id);
            })
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

        $restaurantOwnerId = $employee->user_id;

        $ingredients = Ingredients::whereHas('restaurant', function ($query) use ($employee) {
                $query->where('user_id', $employee->user_id);
            })
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

        // Resolve the actual restaurant_data.id for this employee's restaurant owner
        $restaurant = Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (! $restaurant) {
            return redirect()->back()->with('error', 'No restaurant data found for this employee.');
        }
        $restaurantId = $restaurant->id;

        $validated = $request->validate([
            'type' => ['required', Rule::in(['damage', 'spoilage'])],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
                function ($attribute, $value, $fail) use ($employee) {
                    $ingredient = Ingredients::where('ingredient_id', $value)
                        ->whereHas('restaurant', function ($query) use ($employee) {
                            $query->where('user_id', $employee->user_id);
                        })
                        ->first();
                    if (!$ingredient) {
                        $fail('One or more selected ingredients do not belong to your restaurant.');
                    }
                },
            ],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'ingredients.*.unit' => ['required', 'string', 'max:50'],
            'ingredients.*.estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $successCount = 0;
        $failedCount = 0;
        $logsCreated = [];

        // Loop through each ingredient and create separate logs
        foreach ($validated['ingredients'] as $ingredientData) {
            try {
                // Load ingredient for cost calculation and stock deduction
                $ingredient = Ingredients::find($ingredientData['ingredient_id']);

                if (!$ingredient) {
                    $failedCount++;
                    continue;
                }

                // Auto-calculate estimated cost if not provided
                $estimatedCost = $ingredientData['estimated_cost'] ?? null;
                if (empty($estimatedCost) && $ingredient->cost_per_unit > 0) {
                    $estimatedCost = $ingredientData['quantity'] * $ingredient->cost_per_unit;
                }

                // Create damage/spoilage log entry
                $log = DamageSpoilageLog::create([
                    'restaurant_id' => $restaurantId,
                    'user_id' => $employee->employee_id,
                    'ingredient_id' => $ingredientData['ingredient_id'],
                    'type' => $validated['type'],
                    'quantity' => $ingredientData['quantity'],
                    'unit' => $ingredientData['unit'],
                    'reason' => $validated['reason'],
                    'notes' => $validated['notes'],
                    'incident_date' => $validated['incident_date'],
                    'estimated_cost' => $estimatedCost,
                ]);

                $logsCreated[] = $log;

                // Deduct the damaged/spoiled quantity from ingredient stock
                try {
                    $ingredient->decreaseStock($ingredientData['quantity'], $ingredientData['unit']);

                    Log::info('Stock deducted for damage/spoilage report', [
                        'damage_spoilage_log_id' => $log->id,
                        'ingredient_id' => $ingredient->ingredient_id,
                        'ingredient_name' => $ingredient->ingredient_name,
                        'quantity' => $ingredientData['quantity'],
                        'unit' => $ingredientData['unit'],
                        'restaurant_id' => $restaurantId,
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    Log::warning('Failed to deduct stock for damage/spoilage report', [
                        'damage_spoilage_log_id' => $log->id,
                        'ingredient_id' => $ingredient->ingredient_id ?? null,
                        'message' => $e->getMessage(),
                    ]);
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to create damage/spoilage log', [
                    'ingredient_id' => $ingredientData['ingredient_id'] ?? null,
                    'message' => $e->getMessage(),
                ]);
                $failedCount++;
            }
        }

        // Build success message
        $message = $successCount > 0
            ? "Successfully recorded {$successCount} damage/spoilage incident" . ($successCount !== 1 ? 's' : '') . '.'
            : 'No damage/spoilage incidents were recorded.';

        if ($failedCount > 0) {
            $message .= " {$failedCount} incident" . ($failedCount !== 1 ? 's' : '') . " failed to process.";
        }

        return redirect()->back()
            ->with($successCount > 0 ? 'success' : 'error', $message);
    }

    public function show($id)
    {
        $employee = Auth::guard('kitchen')->user();

        if (!$employee || strtolower($employee->role->role_name) !== 'kitchen') {
            abort(403, 'Access denied. Kitchen staff only.');
        }

        // Resolve the actual restaurant_data.id for this employee's restaurant owner
        $restaurant = Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (! $restaurant) {
            abort(403, 'No restaurant data found for this employee.');
        }
        $restaurantId = $restaurant->id;

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

        // Resolve the actual restaurant_data.id for this employee's restaurant owner
        $restaurant = Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (! $restaurant) {
            abort(403, 'No restaurant data found for this employee.');
        }
        $restaurantId = $restaurant->id;

        $log = DamageSpoilageLog::forRestaurant($restaurantId)->findOrFail($id);

        $ingredients = Ingredients::whereHas('restaurant', function ($query) use ($employee) {
                $query->where('user_id', $employee->user_id);
            })
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

        // Resolve the actual restaurant_data.id for this employee's restaurant owner
        $restaurant = Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (! $restaurant) {
            abort(403, 'No restaurant data found for this employee.');
        }
        $restaurantId = $restaurant->id;

        $log = DamageSpoilageLog::forRestaurant($restaurantId)->findOrFail($id);

        $validated = $request->validate([
            'ingredient_id' => [
                'required',
                'exists:ingredients,ingredient_id',
                function ($attribute, $value, $fail) use ($employee) {
                    $ingredient = Ingredients::where('ingredient_id', $value)
                        ->whereHas('restaurant', function ($query) use ($employee) {
                            $query->where('user_id', $employee->user_id);
                        })
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

        // Auto-calculate estimated cost if not provided
        if (empty($validated['estimated_cost'])) {
            $ingredient = Ingredients::find($validated['ingredient_id']);
            if ($ingredient && $ingredient->cost_per_unit > 0) {
                $validated['estimated_cost'] = $validated['quantity'] * $ingredient->cost_per_unit;
            }
        }

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

        // Resolve the actual restaurant_data.id for this employee's restaurant owner
        $restaurant = Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (! $restaurant) {
            abort(403, 'No restaurant data found for this employee.');
        }
        $restaurantId = $restaurant->id;

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

        // Resolve the actual restaurant_data.id for this employee's restaurant owner
        $restaurant = Restaurant_Data::where('user_id', $employee->user_id)->first();
        if (! $restaurant) {
            return response()->json(['error' => 'No restaurant data found for this employee.'], 404);
        }
        $restaurantId = $restaurant->id;

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