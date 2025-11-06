<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            // Modify the status enum to include 'voided'
            DB::statement("ALTER TABLE customer_orders MODIFY COLUMN status ENUM('pending', 'in_progress', 'ready', 'served', 'completed', 'cancelled', 'paid', 'voided') DEFAULT 'pending'");

            // Add void tracking fields
            $table->foreignId('voided_by')->nullable()->constrained('employees', 'employee_id')->onDelete('set null');
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            // Remove void tracking fields
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['voided_by', 'voided_at', 'void_reason']);

            // Revert status enum to original values
            DB::statement("ALTER TABLE customer_orders MODIFY COLUMN status ENUM('pending', 'in_progress', 'ready', 'served', 'completed', 'cancelled', 'paid') DEFAULT 'pending'");
        });
    }
};
