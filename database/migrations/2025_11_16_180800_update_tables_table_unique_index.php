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
        Schema::table('tables', function (Blueprint $table) {
            // Drop old unique index on table_number (name from error: tables_table_number_unique)
            $table->dropUnique('tables_table_number_unique');

            // Add composite unique index so each user can have their own 'TO' table
            $table->unique(['user_id', 'table_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            // Drop composite unique index
            $table->dropUnique(['user_id', 'table_number']);

            // Restore old unique index on table_number only
            $table->unique('table_number');
        });
    }
};
