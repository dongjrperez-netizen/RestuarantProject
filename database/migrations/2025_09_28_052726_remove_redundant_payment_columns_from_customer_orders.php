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
        Schema::table('customer_orders', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['cashier_id']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['paid_at']);

            // Drop redundant payment columns (now handled by customer_payments table)
            $table->dropColumn([
                'payment_method',
                'amount_paid',
                'discount_amount',
                'discount_reason',
                'discount_notes',
                'final_amount',
                'change_amount',
                'payment_notes',
                'paid_at',
                'cashier_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            // Re-add the payment columns if rollback is needed
            $table->enum('payment_method', ['cash', 'gcash', 'paypal', 'debit_card'])->nullable()->after('completed_at');
            $table->decimal('amount_paid', 10, 2)->nullable()->after('payment_method');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('amount_paid');
            $table->string('discount_reason')->nullable()->after('discount_amount');
            $table->text('discount_notes')->nullable()->after('discount_reason');
            $table->decimal('final_amount', 10, 2)->nullable()->after('discount_notes');
            $table->decimal('change_amount', 10, 2)->nullable()->after('final_amount');
            $table->text('payment_notes')->nullable()->after('change_amount');
            $table->timestamp('paid_at')->nullable()->after('payment_notes');
            $table->unsignedBigInteger('cashier_id')->nullable()->after('paid_at');

            // Re-add indexes
            $table->index('cashier_id');
            $table->index('payment_method');
            $table->index('paid_at');
        });
    }
};
