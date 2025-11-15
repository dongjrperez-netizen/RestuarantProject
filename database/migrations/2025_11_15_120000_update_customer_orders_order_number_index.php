<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            // Drop the global unique index on order_number
            // Name taken from the original migration where order_number was defined as ->unique()
            $table->dropUnique('customer_orders_order_number_unique');

            // Add a composite unique index so each restaurant can have its own sequence
            $table->unique(
                ['restaurant_id', 'order_number'],
                'customer_orders_restaurant_order_number_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropUnique('customer_orders_restaurant_order_number_unique');

            // Restore the original global unique index on order_number
            $table->unique('order_number', 'customer_orders_order_number_unique');
        });
    }
};