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
        Schema::create('table_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Restaurant owner
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->integer('party_size');
            $table->datetime('reservation_date');
            $table->time('reservation_time');
            $table->integer('duration_minutes')->default(120);
            $table->decimal('reservation_fee', 10, 2)->default(0.00);
            $table->datetime('actual_arrival_time')->nullable(); // Default 2 hours
            $table->datetime('dining_start_time')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();
            $table->datetime('created_at');
            $table->datetime('updated_at');
            $table->datetime('cancelled_at')->nullable();

            $table->index(['table_id', 'reservation_date']);
            $table->index(['user_id', 'status']);
            $table->index(['reservation_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_reservations');
    }
};
