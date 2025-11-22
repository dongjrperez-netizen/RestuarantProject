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
            // Drop existing foreign key constraint
            $table->dropForeign(['supplier_id']);

            // Modify supplier_id to be nullable
            $table->unsignedBigInteger('supplier_id')->nullable()->change();

            // Add new supplier information fields for manual entry
            $table->string('supplier_name', 255)->nullable()->after('supplier_id');
            $table->string('supplier_contact', 100)->nullable()->after('supplier_name');
            $table->string('supplier_email', 255)->nullable()->after('supplier_contact');
            $table->string('supplier_phone', 50)->nullable()->after('supplier_email');

            // Re-add foreign key constraint as nullable
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Drop nullable foreign key
            $table->dropForeign(['supplier_id']);

            // Remove added columns
            $table->dropColumn(['supplier_name', 'supplier_contact', 'supplier_email', 'supplier_phone']);

            // Restore supplier_id as NOT NULL with cascade delete
            $table->unsignedBigInteger('supplier_id')->nullable(false)->change();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->cascadeOnDelete();
        });
    }
};
