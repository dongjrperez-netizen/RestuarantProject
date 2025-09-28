<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_data', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name');
            $table->string('address');
            $table->string('postal_code')->nullable();
            $table->string('contact_number')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_data');
    }
};
