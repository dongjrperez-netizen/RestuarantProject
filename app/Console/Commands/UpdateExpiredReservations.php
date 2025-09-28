<?php

namespace App\Console\Commands;

use App\Models\TableReservation;
use App\Models\Table;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:update-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update table status to available for expired reservations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired reservations...');

        // Find reservations that have expired
        $expiredReservations = TableReservation::with('table')
            ->whereIn('status', ['pending', 'confirmed', 'seated'])
            ->get()
            ->filter(function ($reservation) {
                // Calculate the end time of the reservation
                $reservationDateTime = Carbon::parse($reservation->reservation_date->format('Y-m-d') . ' ' . $reservation->reservation_time->format('H:i:s'));
                $endTime = $reservationDateTime->addMinutes($reservation->duration_minutes ?? 120); // Default 2 hours if no duration set

                return $endTime->isPast();
            });

        $updatedCount = 0;
        $tablesUpdated = [];

        foreach ($expiredReservations as $reservation) {
            // Update reservation status
            $oldStatus = $reservation->status;
            $reservation->update(['status' => 'completed']);

            // Update table status to available if it's currently reserved or occupied
            if (in_array($reservation->table->status, ['reserved', 'occupied'])) {
                $reservation->table->update(['status' => 'available']);
                $tablesUpdated[] = $reservation->table->table_name;
            }

            $this->line("Updated reservation ID {$reservation->id} from '{$oldStatus}' to 'completed' for table {$reservation->table->table_name}");
            $updatedCount++;
        }

        if ($updatedCount > 0) {
            $this->info("Successfully updated {$updatedCount} expired reservations.");
            if (!empty($tablesUpdated)) {
                $this->info("Tables updated to available: " . implode(', ', $tablesUpdated));
            }
        } else {
            $this->info('No expired reservations found.');
        }

        return Command::SUCCESS;
    }
}
