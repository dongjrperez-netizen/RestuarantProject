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
        Schema::create('menu_plan_dishes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_plan_id');
            $table->unsignedBigInteger('dish_id');
            $table->integer('planned_quantity')->default(1);
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack'])->nullable();
            $table->date('planned_date');
            $table->integer('day_of_week')->nullable(); // 1-7 for weekly plans
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('menu_plan_id')->references('menu_plan_id')->on('menu_plans')->onDelete('cascade');
            $table->foreign('dish_id')->references('dish_id')->on('dishes')->onDelete('cascade');
            $table->index(['menu_plan_id', 'planned_date']);
            $table->index(['dish_id', 'planned_date']);
            $table->unique(['menu_plan_id', 'dish_id', 'planned_date', 'meal_type'], 'menu_plan_dish_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_plan_dishes');
    }
};
