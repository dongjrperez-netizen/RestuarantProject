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
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique(); // PayMongo payment ID or internal reference
            $table->unsignedBigInteger('order_id');
            $table->enum('payment_method', ['cash', 'gcash', 'paypal', 'debit_card']);
            $table->decimal('original_amount', 10, 2); // Order total before discount
            $table->decimal('discount_amount', 10, 2)->nullable(); // Discount applied
            $table->decimal('final_amount', 10, 2); // Amount actually paid
            $table->decimal('amount_paid', 10, 2); // Amount received (for cash overpayment)
            $table->decimal('change_amount', 10, 2)->nullable(); // Change given (cash only)
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_id')->nullable(); // PayMongo transaction ID
            $table->string('checkout_session_id')->nullable(); // PayMongo checkout session ID
            $table->json('payment_details')->nullable(); // Store PayMongo response or other details
            $table->text('notes')->nullable(); // Additional payment notes
            $table->unsignedBigInteger('cashier_id')->nullable(); // Who processed the payment
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('order_id')->references('order_id')->on('customer_orders');
            $table->foreign('cashier_id')->references('employee_id')->on('employees');

            // Indexes for performance
            $table->index('order_id');
            $table->index('payment_method');
            $table->index('status');
            $table->index('paid_at');
            $table->index('cashier_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
