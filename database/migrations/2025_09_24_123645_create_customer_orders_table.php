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
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->foreignId('table_id')->constrained('tables')->onDelete('cascade');
            $table->foreignId('reservation_id')->nullable()->constrained('table_reservations')->onDelete('set null');
            $table->foreignId('employee_id')->constrained('employees', 'employee_id')->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained('users')->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('customer_name')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'ready', 'served', 'completed', 'cancelled', 'paid'])->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('reservation_fee', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['table_id', 'status']);
            $table->index(['restaurant_id', 'status']);
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};
