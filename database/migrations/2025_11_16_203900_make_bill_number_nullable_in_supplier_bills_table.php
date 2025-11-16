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
        Schema::table('supplier_bills', function (Blueprint $table) {
            // Allow bill_number to be null so it can be generated in a created event
            $table->string('bill_number', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_bills', function (Blueprint $table) {
            // Revert bill_number to NOT NULL if needed
            $table->string('bill_number', 50)->nullable(false)->change();
        });
    }
};
