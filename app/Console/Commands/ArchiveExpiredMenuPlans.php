<?php

namespace App\Console\Commands;

use App\Models\MenuPlan;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ArchiveExpiredMenuPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu-plans:archive-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive menu plans that have passed their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to archive expired menu plans...');

        $today = Carbon::today();

        // Find all active menu plans that have passed their end date
        $expiredPlans = MenuPlan::expiredButActive()->get();

        if ($expiredPlans->isEmpty()) {
            $this->info('No expired menu plans found.');
            return Command::SUCCESS;
        }

        $archivedCount = 0;

        foreach ($expiredPlans as $plan) {
            // Don't archive default plans automatically - they should remain active as fallbacks
            if ($plan->is_default) {
                $this->line("Skipping default plan: {$plan->plan_name} (ID: {$plan->menu_plan_id})");
                continue;
            }

            // Update the plan to inactive
            $plan->update(['is_active' => false]);

            $this->line("Archived plan: {$plan->plan_name} (ID: {$plan->menu_plan_id}) - Ended: {$plan->end_date->format('Y-m-d')}");
            $archivedCount++;
        }

        $this->info("Successfully archived {$archivedCount} expired menu plans.");

        // Log the activity
        \Log::info('Menu plans archived', [
            'archived_count' => $archivedCount,
            'total_expired' => $expiredPlans->count(),
            'date' => $today->format('Y-m-d')
        ]);

        return Command::SUCCESS;
    }
}
