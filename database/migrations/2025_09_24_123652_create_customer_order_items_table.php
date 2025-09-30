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
        Schema::create('customer_order_items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('order_id')->constrained('customer_orders', 'order_id')->onDelete('cascade');
            $table->foreignId('dish_id')->constrained('dishes', 'dish_id')->onDelete('cascade');
            $table->integer('quantity')->default(1);
             $table->integer('served_quantity')->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->text('special_instructions')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served'])->default('pending');
            $table->boolean('inventory_deducted')->default(false);
            $table->timestamp('inventory_deducted_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('dish_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_order_items');
    }
};
