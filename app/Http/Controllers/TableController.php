<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $tables = Table::where('user_id', Auth::id())
            ->with([
                'reservations' => function($query) {
                    $query->where('status', 'seated')->first();
                }
            ])
            ->orderBy('table_number')
            ->get()
            ->map(function($table) {
                $currentReservation = $table->getCurrentReservation();
                $nextReservation = $table->getNextReservation();

                return [
                    'id' => $table->id,
                    'table_number' => $table->table_number,
                    'table_name' => $table->table_name,
                    'seats' => $table->seats,
                    'status' => $table->status,
                    'description' => $table->description,
                    'x_position' => $table->x_position,
                    'y_position' => $table->y_position,
                    'created_at' => $table->created_at,
                    'updated_at' => $table->updated_at,
                    'current_reservation' => $currentReservation,
                    'next_reservation' => $nextReservation,
                ];
            });

        return Inertia::render('POS/Tables/Index', [
            'tables' => $tables
        ]);
    }

    public function create()
    {
        return Inertia::render('POS/Tables/Create');
    }

    public function store(Request $request)
    {
        // Check if we're creating multiple tables
        if ($request->has('tables') && is_array($request->tables)) {
            // Validate array of tables
            $validated = $request->validate([
                'tables' => 'required|array|min:1',
                'tables.*.table_number' => [
                    'required',
                    'string',
                    'max:10',
                    Rule::unique('tables', 'table_number')->where(function ($query) {
                        return $query->where('user_id', Auth::id());
                    }),
                ],
                'tables.*.table_name' => 'required|string|max:255',
                'tables.*.seats' => 'required|integer|min:1|max:20',
                'tables.*.status' => 'required|in:available,occupied,reserved,maintenance',
                'tables.*.description' => 'nullable|string|max:500',
            ]);

            $createdCount = 0;
            foreach ($validated['tables'] as $tableData) {
                $tableData['user_id'] = Auth::id();
                Table::create($tableData);
                $createdCount++;
            }

            return redirect()->route('pos.tables.index')
                ->with('success', "{$createdCount} table(s) created successfully.");
        }

        // Original single table creation (for backwards compatibility)
        $validated = $request->validate([
            'table_number' => [
                'required',
                'string',
                'max:10',
                Rule::unique('tables', 'table_number')->where(function ($query) {
                    return $query->where('user_id', Auth::id());
                }),
            ],
            'table_name' => 'required|string|max:255',
            'seats' => 'required|integer|min:1|max:20',
            'status' => 'required|in:available,occupied,reserved,maintenance',
            'description' => 'nullable|string|max:500',
            'x_position' => 'nullable|numeric',
            'y_position' => 'nullable|numeric',
        ]);

        $validated['user_id'] = Auth::id();

        Table::create($validated);

        return redirect()->route('pos.tables.index')
            ->with('success', 'Table created successfully.');
    }

    public function show(Table $table)
    {
        $this->authorize('view', $table);

        return Inertia::render('POS/Tables/Show', [
            'table' => $table
        ]);
    }

    public function edit(Table $table)
    {
        $this->authorize('update', $table);

        return Inertia::render('POS/Tables/Edit', [
            'table' => $table
        ]);
    }

    public function update(Request $request, Table $table)
    {
        $this->authorize('update', $table);

        $validated = $request->validate([
            'table_number' => [
                'required',
                'string',
                'max:10',
                Rule::unique('tables', 'table_number')
                    ->ignore($table->id)
                    ->where(function ($query) use ($table) {
                        return $query->where('user_id', $table->user_id);
                    }),
            ],
            'table_name' => 'required|string|max:255',
            'seats' => 'required|integer|min:1|max:20',
            'status' => 'required|in:available,occupied,reserved,maintenance',
            'description' => 'nullable|string|max:500',
        ]);

        $table->update($validated);

        return redirect()->route('pos.tables.index')
            ->with('success', 'Table updated successfully.');
    }

    public function destroy(Table $table)
    {
        $this->authorize('delete', $table);

        $table->delete();

        return redirect()->route('pos.tables.index')
            ->with('success', 'Table deleted successfully.');
    }

    public function updateStatus(Request $request, Table $table)
    {
        $this->authorize('update', $table);

        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance'
        ]);

        $table->update($validated);

        return response()->json(['message' => 'Table status updated successfully.']);
    }
}
