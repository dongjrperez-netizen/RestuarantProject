<?php

namespace App\Console\Commands;

use App\Models\Subscriptionpackage;
use Illuminate\Console\Command;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class SetupPayPalPlans extends Command
{
    protected $signature = 'paypal:setup-plans';

    protected $description = 'Create PayPal billing plans for all subscription packages';

    public function handle()
    {
        $this->info('Connecting to PayPal Sandbox...');

        $provider = new PayPalClient;
        $config = config('paypal');
        $provider->setApiCredentials($config);

        try {
            $token = $provider->getAccessToken();
            $this->info('✓ Successfully connected to PayPal API');
        } catch (\Exception $e) {
            $this->error('✗ Failed to connect to PayPal: '.$e->getMessage());
            $this->error('Check your CLIENT_ID and CLIENT_SECRET in .env file');

            return;
        }

        // First create a product
        $productId = $this->createProduct($provider);
        if (! $productId) {
            $this->error('Failed to create product');

            return;
        }

        $plans = Subscriptionpackage::all();

        if ($plans->isEmpty()) {
            $this->info('No subscription plans found in database. Creating sample plans...');
            $plans = $this->createSamplePlans();
        }

        foreach ($plans as $plan) {
            $this->info("Creating PayPal plan for: {$plan->plan_name}");

            $payload = [
                'product_id' => $productId,
                'name' => $plan->plan_name,
                'description' => "{$plan->plan_name} Subscription - {$plan->plan_duration} days",
                'status' => 'ACTIVE',
                'billing_cycles' => [
                    [
                        'frequency' => [
                            'interval_unit' => 'DAY',
                            'interval_count' => $plan->plan_duration,
                        ],
                        'tenure_type' => 'REGULAR',
                        'sequence' => 1,
                        'total_cycles' => 0, // Infinite
                        'pricing_scheme' => [
                            'fixed_price' => [
                                'value' => $plan->plan_price,
                                'currency_code' => 'PHP',
                            ],
                        ],
                    ],
                ],
                'payment_preferences' => [
                    'auto_bill_outstanding' => true,
                    'setup_fee' => [
                        'value' => '0',
                        'currency_code' => 'PHP',
                    ],
                    'setup_fee_failure_action' => 'CONTINUE',
                    'payment_failure_threshold' => 3,
                ],
            ];

            try {
                $response = $provider->createPlan($payload);

                if (isset($response['id'])) {
                    $plan->update(['paypal_plan_id' => $response['id']]);
                    $this->info("✓ Created: {$response['id']}");
                    $this->info("Plan Name: {$response['name']}");
                } else {
                    $this->error('Failed: '.json_encode($response));
                }
            } catch (\Exception $e) {
                $this->error('Error: '.$e->getMessage());
            }
        }

        $this->info('Finished setting up PayPal plans!');
    }

    protected function createProduct($provider)
    {
        try {
            $productPayload = [
                'name' => config('app.name', 'Restaurant').' Subscriptions',
                'description' => 'Subscription plans for '.config('app.name', 'Restaurant App'),
                'type' => 'SERVICE',
                'category' => 'SOFTWARE',
                'image_url' => 'https://example.com/logo.png',
                'home_url' => 'https://example.com',
            ];

            $response = $provider->createProduct($productPayload);

            return $response['id'] ?? null;

        } catch (\Exception $e) {
            $this->error('Product creation failed: '.$e->getMessage());

            return null;
        }
    }

    protected function createSamplePlans()
    {
        return collect([
            Subscriptionpackage::create([
                'plan_name' => 'Basic',
                'plan_price' => 299,
                'plan_duration' => 30,
            ]),
            Subscriptionpackage::create([
                'plan_name' => 'Premium',
                'plan_price' => 599,
                'plan_duration' => 60,
            ]),
            Subscriptionpackage::create([
                'plan_name' => 'Enterprise',
                'plan_price' => 999,
                'plan_duration' => 90,
            ]),
        ]);
    }
}
