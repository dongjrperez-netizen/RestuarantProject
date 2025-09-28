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
        // Add receiving fields to purchase_orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->enum('delivery_condition', ['excellent', 'good', 'fair', 'poor'])->nullable()->after('actual_delivery_date');
            $table->string('received_by', 100)->nullable()->after('delivery_condition');
            $table->text('receiving_notes')->nullable()->after('received_by');
        });

        // Add quality and discrepancy fields to purchase_order_items table
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->enum('quality_rating', ['excellent', 'good', 'fair', 'poor'])->nullable()->after('notes');
            $table->string('condition_notes', 500)->nullable()->after('quality_rating');
            $table->boolean('has_discrepancy')->default(false)->after('condition_notes');
            $table->text('discrepancy_reason')->nullable()->after('has_discrepancy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_condition', 'received_by', 'receiving_notes']);
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['quality_rating', 'condition_notes', 'has_discrepancy', 'discrepancy_reason']);
        });
    }
};
