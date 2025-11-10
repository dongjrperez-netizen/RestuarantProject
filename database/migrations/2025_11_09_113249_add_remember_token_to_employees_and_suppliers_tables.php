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
        // Add remember_token to employees table if it doesn't exist
        if (!Schema::hasColumn('employees', 'remember_token')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->rememberToken()->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('employees', 'remember_token')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }
    }
};
