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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->string('payment_reference', 100)->unique();
            $table->foreignId('bill_id')->constrained('supplier_bills', 'bill_id')->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurant_data')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers', 'supplier_id')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('payment_amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'credit_card', 'paypal', 'online', 'other'])->default('bank_transfer');
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by_user_id');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
