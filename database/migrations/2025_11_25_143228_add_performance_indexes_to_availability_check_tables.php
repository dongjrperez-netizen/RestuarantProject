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
        // Add indexes to customer_orders table for faster availability checks
        Schema::table('customer_orders', function (Blueprint $table) {
            // Index for filtering by restaurant and date
            $table->index(['restaurant_id', 'ordered_at'], 'idx_orders_restaurant_date');
            // Index for filtering by status
            $table->index('status', 'idx_orders_status');
            // Composite index for the full query pattern
            $table->index(['restaurant_id', 'ordered_at', 'status'], 'idx_orders_availability_check');
        });

        // Add index to customer_order_items for dish_id lookups
        Schema::table('customer_order_items', function (Blueprint $table) {
            $table->index('dish_id', 'idx_order_items_dish');
        });

        // Add indexes to menu_plans for faster active plan lookups
        Schema::table('menu_plans', function (Blueprint $table) {
            // Composite index for finding active plans by date range
            $table->index(['restaurant_id', 'is_active', 'start_date', 'end_date'], 'idx_menu_plans_active');
            // Index for default plan lookups
            $table->index(['restaurant_id', 'is_default', 'is_active'], 'idx_menu_plans_default');
        });

        // Add indexes to menu_plan_dishes for faster dish lookups
        Schema::table('menu_plan_dishes', function (Blueprint $table) {
            // Composite index for finding dishes by plan and date
            $table->index(['menu_plan_id', 'dish_id', 'planned_date'], 'idx_plan_dishes_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('menu_plan_dishes', function (Blueprint $table) {
            $table->dropIndex('idx_plan_dishes_lookup');
        });

        Schema::table('menu_plans', function (Blueprint $table) {
            $table->dropIndex('idx_menu_plans_default');
            $table->dropIndex('idx_menu_plans_active');
        });

        Schema::table('customer_order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_dish');
        });

        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_availability_check');
            $table->dropIndex('idx_orders_status');
            $table->dropIndex('idx_orders_restaurant_date');
        });
    }
};
