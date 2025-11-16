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
            // Make po_number nullable but keep the existing unique index
            $table->string('po_number', 50)->nullable()->change();
        });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('purchase_orders', function (Blueprint $table) {
            // Revert to NOT NULL if needed
            $table->string('po_number', 50)->nullable(false)->change();
        });
    }
};
