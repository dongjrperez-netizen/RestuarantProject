<?php

namespace Database\Seeders;

use App\Models\Subscriptionpackage;
use Illuminate\Database\Seeder;

class SubscriptionLimitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update subscription packages with account limits
        // Basic Plan: 5 employees, 10 suppliers
        Subscriptionpackage::where('plan_name', 'LIKE', '%Basic%')
            ->orWhere('plan_name', 'LIKE', '%basic%')
            ->update([
                'employee_limit' => 5,
                'supplier_limit' => 10,
            ]);

        // Premium Plan: 10 employees, 15 suppliers
        Subscriptionpackage::where('plan_name', 'LIKE', '%Premium%')
            ->orWhere('plan_name', 'LIKE', '%premium%')
            ->update([
                'employee_limit' => 10,
                'supplier_limit' => 15,
            ]);

        // Enterprise Plan: Unlimited (NULL)
        Subscriptionpackage::where('plan_name', 'LIKE', '%Enterprise%')
            ->orWhere('plan_name', 'LIKE', '%enterprise%')
            ->update([
                'employee_limit' => null,
                'supplier_limit' => null,
            ]);

        $this->command->info('Subscription limits have been updated successfully!');
        $this->command->info('Basic: 5 employees, 10 suppliers');
        $this->command->info('Premium: 10 employees, 15 suppliers');
        $this->command->info('Enterprise: Unlimited employees and suppliers');
    }
}
