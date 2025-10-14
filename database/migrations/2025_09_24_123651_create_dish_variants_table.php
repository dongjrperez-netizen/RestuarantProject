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
        Schema::create('dish_variants', function (Blueprint $table) {
            $table->id('variant_id');
            $table->unsignedBigInteger('dish_id');
            $table->string('size_name', 50); // e.g., "Small", "Medium", "Large"
            $table->decimal('price_modifier', 10, 2); // Price for this variant
            $table->decimal('quantity_multiplier', 5, 2)->default(1.00); // Ingredient quantity multiplier
            $table->boolean('is_default')->default(false);
            $table->boolean('is_available')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('dish_id')->references('dish_id')->on('dishes')->onDelete('cascade');
            $table->index(['dish_id', 'is_available']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dish_variants');
    }
};
