<?php

namespace Database\Seeders;

use App\Models\Subscriptionpackage;
use Illuminate\Database\Seeder;

class SubscriptionPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'plan_name' => 'Basic',
                'plan_price' => 299.00,
                'plan_duration' => 365,
                'plan_duration_display' => '1 year',
                'paypal_plan_id' => 'P-84G94722VW815883TNDEOVSY',
                'employee_limit' => 5,
                'supplier_limit' => 10,
            ],
            [
                'plan_name' => 'Premium',
                'plan_price' => 599.00,
                'plan_duration' => 1095,
                'plan_duration_display' => '3 years',
                'paypal_plan_id' => 'P-5E876845P0747933DNDEOVTA',
                'employee_limit' => 10,
                'supplier_limit' => 15,
            ],
            [
                'plan_name' => 'Enterprise',
                'plan_price' => 999.00,
                'plan_duration' => 1826,
                'plan_duration_display' => '5 years',
                'paypal_plan_id' => 'P-6CV00633078361726NDEOVTI',
                'employee_limit' => null,
                'supplier_limit' => null,
            ],
            [
                'plan_name' => 'Free Trial',
                'plan_price' => 0,
                'plan_duration' => 30,
                'plan_duration_display' => null,
                'paypal_plan_id' => 'P-6CV00633078361726NDEOVTI',
                'employee_limit' => null,
                'supplier_limit' => null,
            ],
        ];

        foreach ($plans as $plan) {
            Subscriptionpackage::updateOrCreate(
                ['plan_name' => $plan['plan_name']],
                $plan
            );
        }
    }
}
