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
        Schema::create('damage_spoilage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('ingredient_id');
            $table->unsignedBigInteger('user_id'); // Kitchen staff who reported
            $table->enum('type', ['damage', 'spoilage']);
            $table->decimal('quantity', 10, 3);
            $table->string('unit');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->date('incident_date');
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('restaurant_id')->references('id')->on('restaurant_data')->onDelete('cascade');
            $table->foreign('ingredient_id')->references('ingredient_id')->on('ingredients')->onDelete('cascade');
            $table->foreign('user_id')->references('employee_id')->on('employees')->onDelete('cascade');

            // Indexes for better performance
            $table->index(['restaurant_id', 'incident_date']);
            $table->index(['ingredient_id', 'type']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_spoilage_logs');
    }
};
