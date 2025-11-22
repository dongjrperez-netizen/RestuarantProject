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
        Schema::create('menu_plans', function (Blueprint $table) {
            $table->id('menu_plan_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->string('plan_name');
            $table->enum('plan_type', ['daily', 'weekly']);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('restaurant_data')->onDelete('cascade');
            $table->index(['restaurant_id', 'plan_type', 'start_date']);
            $table->index(['is_active', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_plans');
    }
};
