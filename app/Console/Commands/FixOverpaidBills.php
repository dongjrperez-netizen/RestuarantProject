<?php

namespace App\Console\Commands;

use App\Models\SupplierBill;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixOverpaidBills extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:fix-overpaid {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix bills with negative outstanding amounts (overpayments)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        // Find bills with negative outstanding amounts
        $overpaidBills = SupplierBill::where('outstanding_amount', '<', 0)->get();

        if ($overpaidBills->isEmpty()) {
            $this->info('No overpaid bills found. All bills are in good standing!');
            return 0;
        }

        $this->warn("Found {$overpaidBills->count()} bills with overpayments:");
        $this->newLine();

        $fixed = 0;

        foreach ($overpaidBills as $bill) {
            $this->line("Bill: {$bill->bill_number}");
            $this->line("  Total Amount: ₱".number_format($bill->total_amount, 2));
            $this->line("  Paid Amount: ₱".number_format($bill->paid_amount, 2));
            $this->line("  Outstanding: ₱".number_format($bill->outstanding_amount, 2)." (NEGATIVE)");

            if (!$dryRun) {
                // Fix the bill
                DB::transaction(function () use ($bill) {
                    // Cap paid amount at total amount
                    $correctedPaidAmount = min($bill->paid_amount, $bill->total_amount);
                    $correctedOutstanding = max(0, $bill->total_amount - $correctedPaidAmount);

                    $bill->update([
                        'paid_amount' => $correctedPaidAmount,
                        'outstanding_amount' => $correctedOutstanding,
                        'status' => $correctedOutstanding <= 0 ? 'paid' : $bill->status,
                    ]);

                    $this->info("  ✓ Fixed: Paid=₱".number_format($correctedPaidAmount, 2).", Outstanding=₱".number_format($correctedOutstanding, 2));
                });

                $fixed++;
            } else {
                $correctedPaidAmount = min($bill->paid_amount, $bill->total_amount);
                $correctedOutstanding = max(0, $bill->total_amount - $correctedPaidAmount);
                $this->comment("  Would fix to: Paid=₱".number_format($correctedPaidAmount, 2).", Outstanding=₱".number_format($correctedOutstanding, 2));
            }

            $this->newLine();
        }

        if ($dryRun) {
            $this->info("Dry run complete. Would fix {$overpaidBills->count()} bills.");
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->info("Successfully fixed {$fixed} overpaid bills!");
        }

        return 0;
    }
}
