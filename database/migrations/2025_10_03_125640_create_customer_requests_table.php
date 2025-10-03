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
        Schema::create('customer_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('order_id')->constrained('customer_orders', 'order_id')->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained('dishes', 'dish_id')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients', 'ingredient_id')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurant_data')->cascadeOnDelete();
            $table->enum('request_type', ['exclude', 'allergy'])->default('exclude');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_requests');
    }
};
