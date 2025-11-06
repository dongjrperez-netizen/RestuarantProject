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
        Schema::create('regular_employees', function (Blueprint $table) {
            $table->id('regular_employee_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurant_data')->cascadeOnDelete();
            $table->string('firstname', 100);
            $table->string('lastname', 100);
            $table->string('middle_initial', 10)->nullable();
            $table->integer('age')->nullable();
            $table->date('date_of_birth');
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('user_id');
            $table->index('restaurant_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regular_employees');
    }
};
