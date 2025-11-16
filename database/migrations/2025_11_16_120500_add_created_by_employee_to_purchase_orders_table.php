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
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Track which employee (e.g. manager) created the purchase order, in addition to the owner user.
            if (!Schema::hasColumn('purchase_orders', 'created_by_employee_id')) {
                $table->unsignedBigInteger('created_by_employee_id')->nullable()->after('created_by_user_id');
                $table->foreign('created_by_employee_id')
                    ->references('employee_id')
                    ->on('employees')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_orders', 'created_by_employee_id')) {
                $table->dropForeign(['created_by_employee_id']);
                $table->dropColumn('created_by_employee_id');
            }
        });
    }
};
