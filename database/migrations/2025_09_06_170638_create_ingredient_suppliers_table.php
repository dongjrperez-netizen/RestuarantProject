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
        Schema::create('ingredient_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients', 'ingredient_id')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id')->cascadeOnDelete();
            $table->string('package_unit', 50);
            $table->decimal('package_quantity', 10, 2);
            $table->decimal('package_contents_quantity', 10, 2);
            $table->string('package_contents_unit', 50);
            $table->decimal('package_price', 10, 2);
            $table->decimal('lead_time_days', 5, 2)->default(0);
            $table->decimal('minimum_order_quantity', 10, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_suppliers');
    }
};
