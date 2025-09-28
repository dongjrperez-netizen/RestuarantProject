<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\TableReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Inertia\Inertia;

class TableReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $query = TableReservation::with(['table', 'user'])
            ->where('user_id', $userId)
            ->orderBy('reservation_date')
            ->orderBy('reservation_time');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('reservation_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('reservation_date', '<=', $request->date_to);
        }

        // Default to upcoming reservations
        if (!$request->has('status') && !$request->has('date_from')) {
            $query->upcoming();
        }

        $reservations = $query->paginate(15)->withQueryString();

        return Inertia::render('POS/Reservations/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $tables = Table::where('user_id', $user->id)
            ->orderBy('table_number')
            ->get();

        return Inertia::render('POS/Reservations/Create', [
            'tables' => $tables,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'table_id' => ['required', 'exists:tables,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'party_size' => ['required', 'integer', 'min:1', 'max:20'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'actual_arrival_time' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:480'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check if table belongs to the user
        $table = Table::where('id', $validated['table_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Check if table can be reserved at this time
        $reservationDate = Carbon::parse($validated['reservation_date']);
        $reservationTime = Carbon::parse($validated['reservation_time']);

        if (!$table->canBeReserved($reservationDate, $reservationTime, $validated['duration_minutes'])) {
            return back()->withErrors(['table_id' => 'Table is not available at the selected time.']);
        }

        // Prepare actual arrival time if provided
        $actualArrivalTime = null;
        if (!empty($validated['actual_arrival_time'])) {
            try {
                $actualArrivalTime = Carbon::createFromFormat('Y-m-d H:i', $validated['reservation_date'] . ' ' . $validated['actual_arrival_time']);
            } catch (\Exception $e) {
                // If parsing fails, set to null
                $actualArrivalTime = null;
            }
        }

        // Create reservation
        $reservation = TableReservation::create([
            'table_id' => $validated['table_id'],
            'user_id' => $user->id,
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'customer_email' => $validated['customer_email'],
            'party_size' => $validated['party_size'],
            'reservation_date' => $validated['reservation_date'],
            'reservation_time' => $validated['reservation_time'],
            'actual_arrival_time' => $actualArrivalTime,
            'duration_minutes' => $validated['duration_minutes'],
            'special_requests' => $validated['special_requests'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        // Update table status
        $table->updateStatusBasedOnReservations();

        return redirect()->route('pos.tables.index')
            ->with('success', 'Reservation created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TableReservation $reservation)
    {
        $this->authorize('view', $reservation);

        $reservation->load(['table', 'user']);

        return Inertia::render('POS/Reservations/Show', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TableReservation $reservation)
    {
        $this->authorize('update', $reservation);

        $tables = Table::where('user_id', $reservation->user_id)
            ->orderBy('table_number')
            ->get();

        return Inertia::render('POS/Reservations/Edit', [
            'reservation' => $reservation,
            'tables' => $tables,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TableReservation $reservation)
    {
        $this->authorize('update', $reservation);

        $validated = $request->validate([
            'table_id' => ['required', 'exists:tables,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'party_size' => ['required', 'integer', 'min:1', 'max:20'],
            'reservation_date' => ['required', 'date'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:480'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldTable = $reservation->table;

        $reservation->update($validated);

        // Update table statuses
        $oldTable->updateStatusBasedOnReservations();
        $reservation->table->updateStatusBasedOnReservations();

        return redirect()->route('reservations.show', $reservation)
            ->with('success', 'Reservation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TableReservation $reservation)
    {
        $this->authorize('delete', $reservation);

        $table = $reservation->table;
        $reservation->delete();

        // Update table status
        $table->updateStatusBasedOnReservations();

        return back()->with('success', 'Reservation deleted successfully.');
    }

    /**
     * Confirm a reservation
     */
    public function confirm(TableReservation $reservation)
    {
        $this->authorize('update', $reservation);

        if (!$reservation->canBeCancelled()) {
            return back()->withErrors(['status' => 'Reservation cannot be confirmed.']);
        }

        $reservation->confirm();
        $reservation->table->updateStatusBasedOnReservations();

        return back()->with('success', 'Reservation confirmed successfully.');
    }

    /**
     * Cancel a reservation
     */
    public function cancel(TableReservation $reservation)
    {
        $this->authorize('update', $reservation);

        if (!$reservation->canBeCancelled()) {
            return back()->withErrors(['status' => 'Reservation cannot be cancelled.']);
        }

        $reservation->cancel();
        $reservation->table->updateStatusBasedOnReservations();

        return back()->with('success', 'Reservation cancelled successfully.');
    }

    /**
     * Mark customers as arrived (but not seated yet)
     */
    public function markArrived(TableReservation $reservation)
    {
        $this->authorize('update', $reservation);

        $reservation->markArrived();

        return back()->with('success', 'Customer arrival time recorded.');
    }

    /**
     * Seat customers (mark as seated and start dining)
     */
    public function seat(TableReservation $reservation)
    {
        $this->authorize('update', $reservation);

        if (!$reservation->canBeSeated()) {
            return back()->withErrors(['status' => 'Reservation cannot be seated.']);
        }

        $reservation->seat();

        return back()->with('success', 'Customers seated successfully.');
    }

    /**
     * Complete a reservation
     */
    public function complete(TableReservation $reservation)
    {
        $this->authorize('update', $reservation);

        $reservation->complete();

        return back()->with('success', 'Reservation completed successfully.');
    }

    /**
     * Mark as no-show
     */
    public function noShow(TableReservation $reservation)
    {
        $this->authorize('update', $reservation);

        $reservation->markNoShow();

        return back()->with('success', 'Reservation marked as no-show.');
    }

    /**
     * Get available time slots for a specific table and date
     */
    public function availableSlots(Request $request)
    {
        $request->validate([
            'table_id' => ['required', 'exists:tables,id'],
            'date' => ['required', 'date'],
            'duration' => ['required', 'integer', 'min:30', 'max:480'],
        ]);

        $user = Auth::user();
        $table = Table::where('id', $request->table_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $date = Carbon::parse($request->date);
        $duration = $request->duration;

        // Generate time slots from 9 AM to 9 PM every 30 minutes
        $slots = [];
        $startTime = Carbon::parse($date->format('Y-m-d') . ' 09:00:00');
        $endTime = Carbon::parse($date->format('Y-m-d') . ' 21:00:00');

        while ($startTime->lt($endTime)) {
            $available = $table->canBeReserved($date, $startTime, $duration);

            $slots[] = [
                'time' => $startTime->format('H:i'),
                'display_time' => $startTime->format('g:i A'),
                'available' => $available,
            ];

            $startTime->addMinutes(30);
        }


        return response()->json(['slots' => $slots]);
    }

    /**
     * Manually update expired reservations
     */
    public function updateExpiredReservations()
    {
        $user = Auth::user();

        // Find expired reservations for this user's restaurant
        $expiredReservations = TableReservation::with('table')
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed', 'seated'])
            ->get()
            ->filter(function ($reservation) {
                // Calculate the end time of the reservation
                $reservationDateTime = Carbon::parse($reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time->format('H:i:s'));
                $endTime = $reservationDateTime->addMinutes($reservation->duration_minutes ?? 120);

                return $endTime->isPast();
            });

        $updatedCount = 0;
        $tablesUpdated = [];

        foreach ($expiredReservations as $reservation) {
            // Update reservation status
            $reservation->update(['status' => 'completed']);

            // Update table status to available if it's currently reserved or occupied
            if (in_array($reservation->table->status, ['reserved', 'occupied'])) {
                $reservation->table->update(['status' => 'available']);
                $tablesUpdated[] = $reservation->table->table_name;
            }

            $updatedCount++;
        }

        $message = $updatedCount > 0
            ? "Updated {$updatedCount} expired reservations. Tables made available: " . implode(', ', $tablesUpdated)
            : 'No expired reservations found.';

        return back()->with('success', $message);
    }
}
