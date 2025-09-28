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
        Schema::create('subpayments', function (Blueprint $table) {
            $table->id('subpayment_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10);
            $table->dateTime('subpayment_day_created')->useCurrent();
            $table->string('paypal_transaction_id')->nullable();
            $table->foreignId('payment_id')->constrained('paymentinfos', 'payment_id')->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained('restaurant_data')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subpayments');
    }
};
