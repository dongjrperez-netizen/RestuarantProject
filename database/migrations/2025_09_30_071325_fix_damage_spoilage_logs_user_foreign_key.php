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
        Schema::table('damage_spoilage_logs', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['user_id']);

            // Add the new foreign key constraint to employees table
            $table->foreign('user_id')->references('employee_id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('damage_spoilage_logs', function (Blueprint $table) {
            // Drop the foreign key constraint to employees
            $table->dropForeign(['user_id']);

            // Restore the original foreign key constraint to users table
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
