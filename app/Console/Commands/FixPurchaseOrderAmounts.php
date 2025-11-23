<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixPurchaseOrderAmounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'po:fix-amounts {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix total_amount for purchase orders that have been received but have incorrect amounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Find all purchase orders that have been received/delivered
        $purchaseOrders = PurchaseOrder::with('items')
            ->whereIn('status', ['delivered', 'received', 'partially_delivered'])
            ->whereNotNull('received_by')
            ->get();

        $this->info("Found {$purchaseOrders->count()} received purchase orders to check");
        $this->newLine();

        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $this->withProgressBar($purchaseOrders, function ($po) use (&$updatedCount, &$skippedCount, &$errorCount, $isDryRun) {
            try {
                // Calculate the correct total from items
                $calculatedSubtotal = $po->items->sum('total_price');

                // Skip if already correct (within 0.01 tolerance for decimal precision)
                if (abs($po->total_amount - $calculatedSubtotal) < 0.01) {
                    $skippedCount++;
                    return;
                }

                $oldAmount = $po->total_amount;

                if (!$isDryRun) {
                    DB::beginTransaction();
                    try {
                        $po->update([
                            'subtotal' => $calculatedSubtotal,
                            'total_amount' => $calculatedSubtotal,
                        ]);
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        throw $e;
                    }
                }

                $updatedCount++;

                // Log the change
                \Log::info("PO {$po->po_number}: Updated total_amount from {$oldAmount} to {$calculatedSubtotal}" . ($isDryRun ? ' (DRY RUN)' : ''));

            } catch (\Exception $e) {
                $errorCount++;
                \Log::error("Error fixing PO {$po->po_number}: " . $e->getMessage());
            }
        });

        $this->newLine(2);

        // Summary
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated' . ($isDryRun ? ' (would be)' : ''), $updatedCount],
                ['Skipped (already correct)', $skippedCount],
                ['Errors', $errorCount],
                ['Total processed', $purchaseOrders->count()],
            ]
        );

        if ($isDryRun && $updatedCount > 0) {
            $this->newLine();
            $this->warn("⚠️  This was a dry run. Run without --dry-run to apply changes.");
        } elseif ($updatedCount > 0) {
            $this->newLine();
            $this->info("✅ Successfully updated {$updatedCount} purchase orders!");
        } else {
            $this->newLine();
            $this->info("✅ All purchase orders already have correct amounts!");
        }

        return 0;
    }
}
