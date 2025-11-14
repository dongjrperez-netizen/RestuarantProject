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
            // Drop the existing foreign key constraint
            $table->dropForeign(['supplier_id']);

            // Make supplier_id nullable
            $table->foreignId('supplier_id')->nullable()->change();

            // Re-add foreign key constraint with onDelete set to null
            $table->foreign('supplier_id')
                  ->references('supplier_id')
                  ->on('suppliers')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['supplier_id']);

            // Make supplier_id non-nullable again
            $table->foreignId('supplier_id')->nullable(false)->change();

            // Re-add the original foreign key with cascade delete
            $table->foreign('supplier_id')
                  ->references('supplier_id')
                  ->on('suppliers')
                  ->onDelete('cascade');
        });
    }
};
