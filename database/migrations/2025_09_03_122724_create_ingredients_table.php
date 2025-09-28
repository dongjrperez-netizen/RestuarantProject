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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id('ingredient_id');
            $table->foreignId('restaurant_id')->constrained('restaurant_data')->cascadeOnDelete();
            $table->string('ingredient_name', 150);
            $table->string('base_unit', 50);
            $table->decimal('cost_per_unit', 10, 4)->default(0);
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('packages', 10, 2)->default(0);
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->timestamps();
            $table->index(['restaurant_id', 'ingredient_name']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
